<?php

namespace App\Providers;

use App\Services\ClientOptions\OptionsConfig;
use Illuminate\Support\ServiceProvider;

class AppConfigOptionsProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('client-options', function() {
            return new OptionsConfig();
        });
    }
}
