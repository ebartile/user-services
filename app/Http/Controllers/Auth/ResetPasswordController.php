<?php

namespace App\Http\Controllers\Auth;

use App\Events\UserActivities\PasswordReset;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecaptchaRequest;
use App\Models\User;
use App\Notifications\Auth\EmailToken;
use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    public $emailDecaySeconds = 600;
    public $maxEmailAttempts = 5;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Reset the given user's password.
     *
     * @param  CanResetPassword|User  $user
     * @param  string  $password
     * @return void
     */
    protected function resetPassword($user, $password)
    {
        $this->setUserPassword($user, $password);

        $user->setRememberToken(Str::random(60));

        $user->save();

        event(new PasswordReset($user));

        $this->guard()->login($user);
    }

    /**
     * Send sendCode token
     *
     * @param RecaptchaRequest $request
     */
    public function sendCode(RecaptchaRequest $request)
    {
        $request->validate(['email' => 'required']);

        if ($user = $this->getUser($request)) {
            $this->rateLimitEmail($user, function (User $user) {
                return $user->notify(new EmailToken());
            });
        } else {
            return abort(422, trans('auth.failed'));
        }
    }


    /**
     * Rate limit Email request
     *
     * @param User $user
     * @param Closure $callback
     * @return bool
     */
    protected function rateLimitEmail(User $user, $callback)
    {
        return RateLimiter::attempt(
            "recovery:{$user->email}",
            $this->maxEmailAttempts,
            function () use ($user, $callback) {
                return $callback($user);
            },
            $this->emailDecaySeconds
        );
    }

    /**
     * Find user by Email
     *
     * @return User
     */
    protected function getUser(Request $request)
    {
        return User::where('email', $request->get('email'))->first();
    }

    /**
     * Create broker token
     *
     * @param $user
     * @return string
     */
    protected function createToken($user)
    {
        return $this->broker()->createToken($user);
    }
}

