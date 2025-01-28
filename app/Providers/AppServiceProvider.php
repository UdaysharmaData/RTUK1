<?php

namespace App\Providers;

use App\NotifcationDrivers\SmsDriver;
use App\Services\Filesystem\CustomFilesystemManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
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
        Notification::extend('sms', function () {
            return new SmsDriver();
        });

        if ($this->app->environment('local')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
//        DB::whenQueryingForLongerThan(500, function (Connection $connection) {
//            Log::info('Query taking too long', [
//                'database' => $connection->getDatabaseName(),
//                'query' => $connection->query(),
//                'duration' => $connection->totalQueryDuration(),
//                'route' => request() ?? null,
//            ]);
//        });

//        DB::listen(function ($query) {
//            Log::emergency($query->sql, ['Bindings' => $query->bindings, 'Time' => $query->time]);
//        });

        $this->app->bind(
            \Illuminate\Pagination\LengthAwarePaginator::class,
            \App\Services\ParamAwareLengthAwarePaginator::class
        );

        Storage::extend('s3', function($app, $config) {
            return (new CustomFilesystemManager($app))->createS3Driver($config);
        });
    }
}
