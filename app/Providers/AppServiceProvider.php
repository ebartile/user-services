<?php

namespace App\Providers;

use App\Helpers\CspPolicy;
use App\Helpers\ValueStore;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerValueStore();
        $this->registerCspPolicy();
    }

    /**
     * Register Value Store
     *
     * @return void
     */
    protected function registerValueStore()
    {
        $this->app->singleton(ValueStore::class, function () {
            return ValueStore::make(storage_path('app/settings.json'));
        });
    }

    /**
     * Register CSP Policy
     *
     * @return void
     */
    protected function registerCspPolicy()
    {
        $this->app->singleton(CspPolicy::class, function () {
            return new CspPolicy();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        JsonResource::withoutWrapping();
    }
}
