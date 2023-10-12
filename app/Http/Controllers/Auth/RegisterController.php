<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\AppController;
use App\Events\UserActivities\Registered;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecaptchaRequest;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Foundation\Auth\RedirectsUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use ArrayObject;
use App\Helpers\LocaleManager;
use App\Http\Resources\UserResource;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RedirectsUsers;

    /**
     * Where to redirect users after registration.
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
     * Handle a registration request for the application.
     *
     * @param RecaptchaRequest $request
     * @return RedirectResponse|JsonResponse
     * @throws ValidationException
     * @throws \Throwable
     */
    public function register(RecaptchaRequest $request)
    {
        $this->validator($request->all())->validate();

        $user = $this->create($request->all());

        $this->guard()->login($user);

        if($request->bearerToken()) {
            $request->session()->regenerate();
        }

        event(new Registered($user));

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
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'email'    => ['required', 'string', 'email:rfc,dns,spoof', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:6', Password::min(6)->numbers()],
        ]);
    }

    /**
     * Get country codes
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getCountryCodes()
    {
        return collect(config('countries'))->keys();
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     * @return User
     */
    protected function create(array $data)
    {
        return User::create([
            'name'    => $data['email'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    /**
     * Get the guard to be used during registration.
     *
     * @return StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard();
    }

    /**
     * Get user object
     *
     * @return UserResource
     */
    protected function getAuthUser()
    {
        optional(Auth::user())->updatePresence("online");
        return UserResource::make(Auth::user());
    }

}

