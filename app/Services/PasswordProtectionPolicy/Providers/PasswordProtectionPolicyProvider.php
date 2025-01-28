<?php

namespace App\Services\PasswordProtectionPolicy\Providers;

use App\Services\PasswordProtectionPolicy\Console\Commands\ClearPasswordHistory;
use App\Services\PasswordProtectionPolicy\Console\Commands\SendPasswordResetReminder;
use App\Services\PasswordProtectionPolicy\Observers\PasswordProtectableObserver;
use App\Services\PasswordProtectionPolicy\PasswordProtectionService;
use Illuminate\Support\ServiceProvider;

class PasswordProtectionPolicyProvider extends ServiceProvider
{
    /**
     * Bootstrap custom services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ClearPasswordHistory::class,
                SendPasswordResetReminder::class
            ]);
        }

        // if specified model exists, boot up an observer for it
        $model = config('passwordprotectionpolicy.observe.model');
        class_exists($model) && $model::observe(PasswordProtectableObserver::class);

        // boot a PasswordProtectionService for any given model that implements KeepPasswordHistory
        // eg. request()->user() in this case
        (new PasswordProtectionService(request()->user()))->defaultRules();
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        //
    }
}
