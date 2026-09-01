<?php

namespace App\Providers;

use App\Socialite\ZohoProvider;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory;

class ZohoServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $socialite = $this->app->make(Factory::class);

        $socialite->extend(
            'zoho',
            function ($app) use ($socialite) {
                $config = $app['config']['services.zoho'];

                return $socialite->buildProvider(ZohoProvider::class, $config);
            }
        );
    }
}
