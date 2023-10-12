<?php

namespace App\Http\Controllers;

use App\Events\UserActivities\PasswordChanged;
use App\Events\UserActivities\UpdatedPicture;
use App\Events\UserActivities\UpdatedPreference;
use App\Events\UserActivities\UpdatedProfile;
use App\Events\UserActivities\VerifiedEmail;
use App\Http\Resources\UserActivityResource;
use App\Http\Resources\UserResource;
use ArrayObject;
use App\Models\User;
use App\Rules\ProtectField;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Get user data
     *
     * @return \App\Http\Resources\UserResource
     */
    public function auth()
    {
        return UserResource::make(Auth::user());
    }

    /**
     * Update profile
     *
     * @param Request $request
     * @throws ValidationException
     */
    public function update(Request $request)
    {
        Auth::user()->acquireLock(function (User $user) use ($request) {
            $user->fill($this->validate($request, [
                'email'    => ['email:rfc,dns,spoof', 'max:255', $this->uniqueRule($user)],
            ]));

            $user->profile->fill($this->validate($request, [
                'last_name'  => ['string', 'max:100', new ProtectField($user->profile->last_name)],
                'first_name' => ['string', 'max:100', new ProtectField($user->profile->first_name)],
                'dob'        => ['date', 'before:-18 years'],
                'bio'        => ['nullable', 'string', 'max:1000'],
            ]));

            tap($user)->save()->profile->save();
            event(new UpdatedProfile($user));
        });

        $auth_token = ""; 

        if($user = Auth::user()) {
            $auth_token = $user->createToken('Token Name')->accessToken;
        }

        $data = [
            'name'         => config('app.name'),
            'broadcast'    => $this->getBroadcastConfig(),
            'settings'     => [
                'recaptcha' => [
                    'enable'  => config('services.recaptcha.enable'),
                    'sitekey' => config('services.recaptcha.sitekey'),
                    'size'    => config('services.recaptcha.size'),
                ],
            ],
            'auth'         => [
                'credential' => config('auth.credential'),
                'user'       => $this->getAuthUser(),
            ],
            'csrfToken'    => csrf_token(),
            'auth_token'   => $auth_token
        ];

        return response()->json($data);
    }

    /**
     * Get user object
     *
     * @return UserResource
     */
    protected function getAuthUser()
    {
        Auth::user()?->updatePresence('online');

        return UserResource::make(Auth::user());
    }

    /**
     * Get event broadcasting config
     *
     * @return array
     */
    protected function getBroadcastConfig()
    {
        $connection = new ArrayObject();
        $driver = config('broadcasting.default');

        if ($driver == 'pusher') {
            $pusher = config("broadcasting.connections")[$driver];
            $connection['key'] = $pusher['key'];
            $connection['host'] = env('APP_HOST');
            $options = $pusher['options'];
            $connection['cluster'] = $options['cluster'];
            $connection['port'] = $options['port'];
        }

        if ($driver == 'redis') {
                $options = config("broadcasting.connections")[$driver];
                $connection['host'] = $options['host'];
                $connection['port'] = $options['port'];
        }

        return compact('connection', 'driver');
    }

    /**
     * Get unique rule
     *
     * @return \Illuminate\Validation\Rules\Unique
     */
    protected function uniqueRule(User $user)
    {
        return Rule::unique('users')->ignore($user);
    }

    /**
     * Verify email with token
     *
     * @param Request $request
     * @throws ValidationException
     */
    public function verifyEmailWithToken(Request $request)
    {
        if (Auth::user()->isEmailVerified()) {
            abort(403, trans('verification.email_already_verified'));
        }

        $validated = $this->validate($request, [
            'token' => 'required|string|min:6',
        ]);

        if (Auth::user()->validateEmailToken($validated['token'])) {
            Auth::user()->update(['email_verified_at' => now()]);
        } else {
            abort(422, trans('verification.invalid_email_token'));
        }

        event(new VerifiedEmail(Auth::user()));
    }

    /**
     * Upload Picture
     *
     * @param Request $request
     * @throws ValidationException
     */
    public function uploadPicture(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimetypes:image/png,image/jpeg|file|max:100',
        ]);

        $file = $request->file('file');
        $picture = savePublicFile($file, Auth::user()->path(), "avatar.{$file->extension()}");

        Auth::user()->profile->update(['picture' => $picture]);
        event(new UpdatedPicture(Auth::user()));

        $auth_token = ""; 

        if($user = Auth::user()) {
            $auth_token = $user->createToken('Token Name')->accessToken;
        }

        $data = [
            'name'         => config('app.name'),
            'broadcast'    => $this->getBroadcastConfig(),
            'settings'     => [
                'recaptcha' => [
                    'enable'  => config('services.recaptcha.enable'),
                    'sitekey' => config('services.recaptcha.sitekey'),
                    'size'    => config('services.recaptcha.size'),
                ],
            ],
            'auth'         => [
                'credential' => config('auth.credential'),
                'user'       => $this->getAuthUser(),
            ],
            'csrfToken'    => csrf_token(),
            'auth_token'   => $auth_token
        ];

        return response()->json($data);
    }

    /**
     * Change user password
     *
     * @param Request $request
     * @throws ValidationException|AuthenticationException
     */
    public function changePassword(Request $request)
    {
        $this->validate($request, [
            'old_password' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!Hash::check($value, Auth::user()->password)) {
                        $fail(trans('auth.invalid_password'));
                    }
                },
            ],

            'password' => [
                'required', 'string', 'min:8', 'max:255',
                'different:old_password', Password::defaults(),
            ],
        ]);

        $password = $request->get('password');

        Auth::user()->update(['password' => Hash::make($password)]);

        event(new PasswordChanged(Auth::user()));

    }

    /**
     * Update presence as online
     */
    public function setOnline()
    {
        Auth::user()->updatePresence('online');
    }

    /**
     * Update presence as away
     */
    public function setAway()
    {
        Auth::user()->updatePresence('away');
    }

    /**
     * Update presence as offline
     */
    public function setOffline()
    {
        Auth::user()->updatePresence('offline');
    }
}
