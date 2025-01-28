<?php

use Illuminate\Support\Facades\Facade;

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Files path
    |--------------------------------------------------------------------------
    |
    | These are the path to files in the application.
    |
    */

    'uploads_path' => env('UPLOADS_PATH', '/dev/null'),

    'media_path' => env('MEDIA_PATH', '/dev/null'),

    'images_path' => env('IMAGES_PATH', '/dev/null'),

    'audios_path' => env('AUDIOS_PATH', '/dev/null'),

    'documents_path' => env('DOCUMENTS_PATH', '/dev/null'),

    'csvs_path' => env('CSVS_PATH', '/dev/null'),

    'pdfs_path' => env('PDFS_PATH', '/dev/null'),

    'sfc_path' => env('SFC_PATH', '/dev/null'),

    /*
    |--------------------------------------------------------------------------
    | Values path
    |--------------------------------------------------------------------------
    |
    | These are the path to files in the application.
    |
    */

    'event_withdrawal_weeks' => env('EVENT_WITHDRAWAL_WEEKS', 2),

    'participant_registration_charge_rate' => env('PARTICIPANT_REGISTRATION_CHARGE_RATE', 4),

    'corporate_rate' => env('CORPORATE_RATE', 5),

    'corporate_value' => env('CORPORATE_VALUE', 1),

    'corporate_currency' => env('CORPORATE_CURRENCY', '£'),

    /*
    |--------------------------------------------------------------------------
    | Google API Key
    |--------------------------------------------------------------------------
    |
    */

    'google_api_key' => env('GOOGLE_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | LDT Token
    |--------------------------------------------------------------------------
    |
    */

    'ldt_token' => env('LDT_TOKEN'),
    'external_enquiries_limit' => env('EXTERNAL_ENQUIRIES_LIMIT', 100),


    /*
    |--------------------------------------------------------------------------
    | Password History Number
    |--------------------------------------------------------------------------
    |
    | The number of most recent old passwords to check against the user's newly
    | entered password to ensure they are not the same.
    |
    */

    'password_history_num' => env('PASSWORD_HISTORY_NUM', 5),

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => [

        /*
         * Laravel Framework Service Providers...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        /*
         * Package Service Providers...
         */

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
//        App\Providers\TelescopeServiceProvider::class,
        \App\Services\Auth\Providers\ApiPasswordResetServiceProvider::class,
        \App\Services\PasswordProtectionPolicy\Providers\PasswordProtectionPolicyProvider::class,
        \App\Services\Importer\Providers\ImporterServiceProvider::class,
        \App\Providers\AppConfigOptionsProvider::class,
        \App\Services\SocialiteMultiTenancySupport\Providers\SocialiteMultiTenancyProvider::class,
        \App\Providers\GoogleDriveServiceProvider::class,
        Spatie\Activitylog\ActivitylogServiceProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => Facade::defaultAliases()->merge([
        'Arr'  => Illuminate\Support\Arr::class,
        'Str'  => Illuminate\Support\Str::class,
        'Rule'  => Illuminate\Validation\Rule::class,
        'Log'  => Illuminate\Support\Facades\Log::class,
        'Redis' => Illuminate\Support\Facades\Redis::class,
        'client-options' => \App\Services\ClientOptions\OptionsConfig::class,
        'socialite-multi-tenancy' => \App\Services\SocialiteMultiTenancySupport\SocialiteMultiTenantManager::class,
    ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | This value can help to easily customize API version in RouteServiceProvider.php
    | Use case: routing API requests based on version
    |
    */

    'api_version' => 'v' . env('API_VERSION', 1),

    'analytics' => [
        'change_period_in_days' => 7
    ],

    'rate_limit_whitelist' => env('RATE_LIMIT_WHITELIST', ''),
];
