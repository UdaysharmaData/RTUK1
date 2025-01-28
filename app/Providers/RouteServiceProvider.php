<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * @var string[]
     */
    protected static array $rateLimitWhitelist;

    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * The defined namespaces are for some route groups.
     *
     * @var string
     */
    protected $portalNamespace = 'App\Http\Controllers\Portal';
    protected $clientNamespace = 'App\Http\Controllers\Client';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        self::$rateLimitWhitelist = explode(",", config('app.rate_limit_whitelist'));

        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware(['api', 'client.valid'])
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::prefix('api/v1/portal')
                ->middleware(['api', 'portal', 'client.valid'])
                ->namespace($this->namespace)
                ->group(base_path('routes/portal.php'));

            Route::prefix('api/v1/client')
                ->middleware(['api', 'client', 'client.valid'])
                ->namespace($this->namespace)
                ->group(base_path('routes/client.php'));

            Route::prefix('api/v1/payment')
                ->middleware(['api'])
                ->namespace($this->namespace)
                ->group(base_path('routes/payment.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            $limit = in_array((string) $request->ip(), self::$rateLimitWhitelist)
                ? Limit::none()
                : Limit::perMinute(60);

            return $limit->by($request->user()?->id ?: $request->ip());
        });
    }
}
