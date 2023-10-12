<?php

namespace App\Providers;

use App\Events\UserActivities\EmailChanged;
use App\Events\UserActivities\EnabledTwoFactor;
use App\Events\UserActivities\LoggedIn;
use App\Events\UserActivities\PasswordChanged;
use App\Events\UserActivities\PasswordReset;
use App\Events\UserActivities\UpdatedPicture;
use App\Events\UserActivities\UpdatedPreference;
use App\Events\UserActivities\UpdatedProfile;
use App\Events\UserActivities\VerifiedEmail;
use App\Listeners\LogUserActivity;
use App\Listeners\SendEmailVerificationNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class        => [SendEmailVerificationNotification::class],
        LoggedIn::class          => [LogUserActivity::class],
        PasswordReset::class     => [LogUserActivity::class],
        PasswordChanged::class   => [LogUserActivity::class],
        EmailChanged::class      => [LogUserActivity::class],
        UpdatedPicture::class    => [LogUserActivity::class],
        UpdatedPreference::class => [LogUserActivity::class],
        UpdatedProfile::class    => [LogUserActivity::class],
        VerifiedEmail::class     => [LogUserActivity::class],
    ];

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
