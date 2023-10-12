<?php

namespace App\Models;

use App\Events\UserActivities\EmailChanged;
use App\Events\UserPresenceChanged;
use App\Helpers\Token;
use App\Models\Traits\Lock;
use App\Notifications\Auth\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

/**
 * @mixin IdeHelperUser
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes, Lock, HasApiTokens;

    protected $verificationHelperAttribute;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'deactivated_until',
        'password',
        'email_verified_at',
        'presence',
        'last_seen_at',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at'     => 'datetime',
        'deactivated_until'     => 'datetime',
        'last_seen_at'          => 'datetime',
        'last_login_at'         => 'datetime',
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['profile'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::updating(function (self $user) {
            if ($user->isDirty('email')) {
                event(new EmailChanged($user));
                $user->email_verified_at = null;
            }

            if ($user->isDirty('presence') && $user->presence === "online") {
                $user->last_seen_at = $user->freshTimestamp();
            }
        });

        static::created(function (self $user) {
            $user->profile()->save(new UserProfile);
        });
    }

    /**
     * Get path for profile
     *
     * @return string
     */
    public function path(): string
    {
        return "profile/{$this->id}";
    }

    /**
     * Generate email token
     *
     * @return array
     */
    public function generateEmailToken(): array
    {
        return app(Token::class)->generate($this->email);
    }

    /**
     * Validate email token
     *
     * @param $token
     * @return bool
     */
    public function validateEmailToken($token): bool
    {
        return app(Token::class)->validate($this->email, $token);
    }

    /**
     * Check if user's email is verified
     *
     * @return bool
     */
    public function isEmailVerified(): bool
    {
        return (bool) $this->email_verified_at;
    }

    /**
     * long_term attribute
     *
     * @return bool
     */
    public function getLongTermAttribute(): bool
    {
        return $this->isLongTerm();
    }

    /**
     * active attribute
     *
     * @return bool
     */
    public function getActiveAttribute(): bool
    {
        return $this->isActive();
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmail);
    }


    /**
     * User profile
     *
     * @return HasOne
     */
    public function profile()
    {
        return $this->hasOne(UserProfile::class, 'user_id', 'id');
    }

    /**
     * Get participation details
     *
     * @return array
     */
    public function getParticipantDetails(): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'presence'     => $this->presence,
            'last_seen_at' => $this->last_seen_at,
            'picture'      => $this->profile->picture,
        ];
    }

    /**
     * Get country name
     *
     * @return Attribute
     */
    protected function countryName(): Attribute
    {
        return Attribute::make(
            get: fn() => config("countries.$this->country")
        )->shouldCache();
    }

    /**
     * Update authenticated user's presence
     *
     * @param $presence
     * @return void
     */
    public function updatePresence($presence)
    {
        $this->update(['presence' => $presence]);
        broadcast(new UserPresenceChanged($this));
    }

    /**
     * Check if user is deactivated
     *
     * @return bool
     */
    public function deactivated(): bool
    {
        return $this->deactivated_until && $this->deactivated_until > now();
    }

    /**
     * Check if user is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return !$this->deactivated();
    }

    /**
     * User's address
     *
     * @return HasOne
     */
    public function address()
    {
        return $this->hasOne(UserAddress::class, 'user_id', 'id');
    }

    /**
     * permission: view user
     *
     * @param User $user
     * @return bool
     */
    public function canViewUser(self $user): bool
    {
        return $this->is($user);
    }

    /**
     * permission: update user
     *
     * @param User $user
     * @return bool
     */
    public function canUpdateUser(self $user): bool
    {
        return $this->isNot($user) && $this->can('manage_users');
    }

    /**
     * permission: delete user
     *
     * @param User $user
     * @return bool
     */
    public function canDeleteUser(self $user): bool
    {
        return $this->isNot($user) && $this->can('delete_users');
    }

    /**
     * Check if user is offline
     *
     * @return bool
     */
    public function isUnavailable(): bool
    {
        return !$this->last_seen_at || now()->diffInMinutes($this->last_seen_at) > 30;
    }

    /**
     * Check if this is a "long term" user
     *
     * @return bool
     */
    public function isLongTerm()
    {
        return now()->diffInMonths($this->created_at) >= 3;
    }

    /**
     * Retrieve the User for a bound value.
     *
     * @param mixed $value
     * @param string|null $field
     * @return Model
     */
    public function resolveRouteBinding($value, $field = null)
    {
        try {
            return $this->resolveRouteBindingQuery($this, $value, $field)->firstOrFail();
        } catch (ModelNotFoundException) {
            abort(404, trans('user.not_found'));
        }
    }
}
