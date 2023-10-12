<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\AppController;
use App\Events\UserActivities\LoggedIn;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecaptchaRequest;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Foundation\Auth\RedirectsUsers;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Events\UserActivities\VerifiedEmail;
use App\Notifications\Auth\EmailToken;
use Illuminate\Support\Facades\RateLimiter;
use ArrayObject;
use App\Helpers\LocaleManager;
use App\Http\Resources\UserResource;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use RedirectsUsers, ThrottlesLogins;

    public $emailDecaySeconds = 30;
    public $maxEmailAttempts = 5;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Handle a login request to the application.
     *
     * @param RecaptchaRequest $request
     * @return JsonResponse|RedirectResponse|void
     *
     * @throws ValidationException
     */
    public function login(RecaptchaRequest $request)
    {
        $this->validateLogin($request);

        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            return $this->sendLoginResponse($request);
        }

        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Validate the user login request.
     *
     * @param RecaptchaRequest $request
     * @return void
     *
     * @throws ValidationException
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password'        => 'required|string',
        ]);
    }

    /**
     * Resend Token
     *
     * @param Request $request
     * @return bool
     * @throws ValidationException
     */
    protected function resendToken(Request $request)
    {
        $this->validateLogin($request);

        if ($user = $this->getUser($request)) {
            $user->notify(new EmailToken());
            return response()->json([], 200);
        }

        return response()->json([], 400);

    }

    /**
     * Rate limit email request
     *
     * @param User $user
     * @param Closure $callback
     * @return bool
     */
    protected function rateLimitEmail(User $user, $callback)
    {
        return RateLimiter::attempt(
            "login:{$user->email}",
            $this->maxEmailAttempts,
            function () use ($user, $callback) {
                return $callback($user);
            },
            $this->emailDecaySeconds
        );
    }

    /**
     * Attempt to log the user into the application.
     *
     * @param Request $request
     * @return bool
     * @throws ValidationException
     */
    protected function attemptLogin(Request $request)
    {

        return $this->guard()->attempt(
            $this->credentials($request), (bool) $request->get('remember')
        );
    }

    /**
     * Get user from request
     *
     * @param Request $request
     * @return User
     */
    protected function getUser(Request $request)
    {
        return User::where($this->username(), $request->get($this->username()))->first();
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @param Request $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        return $request->only($this->username(), 'password');
    }

    /**
     * Send the response after the user was authenticated.
     *
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    protected function sendLoginResponse(Request $request)
    {
        $this->clearLoginAttempts($request);

        if($request->bearerToken()) {
            $request->session()->regenerate();
        }


        $user = $this->guard()->user();
        $user->update(['last_login_at' => now()]);

        event(new LoggedIn($user));

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
     * Get the failed login response instance.
     *
     * @param Request $request
     * @return void
     *
     * @throws ValidationException
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    protected function username()
    {
        return 'email';
    }

    /**
     * Log the user out of the application.
     *
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function logout(Request $request)
    {
        if(is_null($request->user())) {
            // pass
        } else if($request->bearerToken()) {
            $request->user()->token()->revoke();
        } else {
            $this->guard()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return !$request->wantsJson()
            ? redirect()->to($this->redirectPath())
            : response()->json([], 204);
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard();
    }

}


