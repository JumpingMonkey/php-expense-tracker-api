<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class JwtAuthServiceProvider extends ServiceProvider
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
        // Register the JWT auth guard
        $this->app['auth']->extend('jwt', function ($app, $name, array $config) {
            return new \PHPOpenSourceSaver\JWTAuth\JWTGuard(
                $app['PHPOpenSourceSaver\JWTAuth\JWT'],
                $app['auth']->createUserProvider($config['provider']),
                $app['request'],
                $app['events']
            );
        });
    }
}
