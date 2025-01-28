<?php

namespace App\Services\SocialiteMultiTenancySupport\Providers;

use App\Services\SocialiteMultiTenancySupport\SocialiteMultiTenantManager;
use Illuminate\Support\ServiceProvider;
use function request;

class SocialiteMultiTenancyProvider extends ServiceProvider
{
    /**
     * Register the application services.
     */
    public function register()
    {
        $this->app->bind('socialite-multi-tenancy', function ($app) {
            return new SocialiteMultiTenantManager($app, request('key'));
        });
    }
}
