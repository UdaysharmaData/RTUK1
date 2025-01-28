<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            \App\Http\Middleware\EnsureJsonResponse::class,
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
//            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
//            \App\Http\Middleware\SiteMiddleware::class,
        ],

        'portal' => [
//            \App\Http\Middleware\AccessMiddleware::class
        ],

        'client' => [
            \App\Http\Middleware\ClientAuthenticationMiddleware::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array<string, class-string|string>
     */
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'role' => \App\Http\Middleware\RoleMiddleware::class,
        'permission' => \App\Http\Middleware\PermissionMiddleware::class,
        'site' => \App\Http\Middleware\SiteMiddleware::class,
        'access' => \App\Http\Middleware\AccessMiddleware::class,
        'verified.email' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'verified.phone' => \App\Http\Middleware\EnsurePhoneIsVerified::class,
        'auth.socials' => \App\Http\Middleware\EnsureValidSocialAuthProvider::class,
        'passphrase.verify' => \App\Services\PasswordProtectionPolicy\Middleware\RequirePassphrasePolicy::class,
        'admin' => \App\Http\Middleware\UserIsAdmin::class,
        'participant' => \App\Http\Middleware\UserIsParticipant::class,
        'verified.2fa' => \App\Http\Middleware\VerifyTwoFactorToken::class,
        'user.status.active' => \App\Http\Middleware\UserStatusIsActive::class,
        'admin.general' => \App\Http\Middleware\UserIsGeneralAdmin::class,
        'verify.recaptcha' => \App\Http\Middleware\VerifyRecaptchaMiddleware::class,
        'auth:client' => \App\Http\Middleware\ClientAuthenticationMiddleware::class,
        'redirect' => \App\Http\Middleware\HandleRedirectMiddleware::class,
        'client.valid' => \App\Http\Middleware\EnsureApiRequestHostIsValidClient::class,
    ];
}
