<?php

namespace App\Services\Auth\Providers;

use App\Services\Auth\Services\ApiPasswordBrokerManager;
use Illuminate\Auth\Passwords\PasswordResetServiceProvider;

class ApiPasswordResetServiceProvider extends PasswordResetServiceProvider
{
    protected function registerPasswordBroker()
    {
        $this->app->singleton('auth.password', function ($app) {
            return new ApiPasswordBrokerManager($app);
        });

        $this->app->bind('auth.password.broker', function ($app) {
            return $app->make('auth.password')->broker();
        });
    }
}
