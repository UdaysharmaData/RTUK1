<?php

use App\Modules\Setting\Enums\SiteCodeEnum;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', env('LOG_STACK_CHANNELS', 'single')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/general.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'daily' => [
            // override configuration for daily channel
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],

//            default configuration for daily channel
//            'driver' => 'daily',
//            'path' => storage_path('logs/general/general.log'),
//            'level' => env('LOG_LEVEL_SINGLE', 'debug'),
//            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('APP_NAME', 'Laravel'),
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL_SLACK', 'critical'),
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        'dataimport' => [
            'driver' => 'daily',
            'path' => storage_path('logs/dataimport.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'test' => [
            'driver' => 'daily',
            'path' => storage_path('logs/test/test.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        'admin' => [
            'driver' => 'daily',
            'path' => storage_path('logs/admin/admin.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        'developer' => [
            'driver' => 'daily',
            'path' => storage_path('logs/developer/developer.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        'adminanddeveloper' => [
            'driver' => 'daily',
            'path' => storage_path('logs/adminanddeveloper/adminanddeveloper.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        'ldtfetch' => [
            'driver' => 'daily',
            'path' => storage_path('logs/ldt/fetch/fetch.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        'ldtoffer' => [
            'driver' => 'daily',
            'path' => storage_path('logs/ldt/offer/offer.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],
        
        'ldtoffersingleparticipant' => [
            'driver' => 'daily',
            'path' => storage_path('logs/ldt/offersingle/offer.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],


        'ldtofferprocess' => [
            'driver' => 'daily',
            'path' => storage_path('logs/ldt/offer/process.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        'sitemap' => [
            'driver' => 'daily',
            'path' => storage_path('logs/sitemap/sitemap.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        'sitemapprocess' => [
            'driver' => 'daily',
            'path' => storage_path('logs/sitemap/sitemapprocess.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        'enquiry' => [
            'driver' => 'daily',
            'path' => storage_path('logs/enquiry/enquiry.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        'enquiryprocess' => [
            'driver' => 'daily',
            'path' => storage_path('logs/enquiry/process.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        'processes' => [
            'driver' => 'daily',
            'path' => storage_path('logs/process/processes.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        'stripe' => [
            'driver' => 'daily',
            'path' => storage_path('logs/stripe/stripe.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        'stripepaymentintent' => [
            'driver' => 'daily',
            'path' => storage_path('logs/stripe/paymentintent.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        'stripecharge' => [
            'driver' => 'daily',
            'path' => storage_path('logs/stripe/charge.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        'stripepaymentmethod' => [
            'driver' => 'daily',
            'path' => storage_path('logs/stripe/paymentmethod.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        'stripepaymentlink' => [
            'driver' => 'daily',
            'path' => storage_path('logs/stripe/paymentlink.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        'command' => [
            'driver' => 'daily',
            'path' => storage_path('logs/commands/commands.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        'mailexception' => [
            'driver' => 'daily',
            'path' => storage_path('logs/mail/exception.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],



        SiteCodeEnum::RunForCharity->value . 'admin' => [
            'driver' => 'daily',
            'path' => storage_path('logs/admin/' . SiteCodeEnum::RunForCharity->value . '/admin.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunForCharity->value . 'developer' => [
            'driver' => 'daily',
            'path' => storage_path('logs/developer/' . SiteCodeEnum::RunForCharity->value . '/developer.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunForCharity->value . 'adminanddeveloper' => [
            'driver' => 'daily',
            'path' => storage_path('logs/adminanddeveloper/' . SiteCodeEnum::RunForCharity->value . '/adminanddeveloper.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunForCharity->value . 'ldtfetch' => [
            'driver' => 'daily',
            'path' => storage_path('logs/ldt/fetch/' . SiteCodeEnum::RunForCharity->value . '/fetch.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunForCharity->value . 'ldtoffer' => [
            'driver' => 'daily',
            'path' => storage_path('logs/ldt/offer/' . SiteCodeEnum::RunForCharity->value . '/offer.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunForCharity->value . 'ldtofferprocess' => [
            'driver' => 'daily',
            'path' => storage_path('logs/ldt/offer/' . SiteCodeEnum::RunForCharity->value . '/process.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunForCharity->value . 'sitemap' => [
            'driver' => 'daily',
            'path' => storage_path('logs/sitemap/' . SiteCodeEnum::RunForCharity->value . '/sitemap.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunForCharity->value . 'sitemapprocess' => [
            'driver' => 'daily',
            'path' => storage_path('logs/sitemap/' . SiteCodeEnum::RunForCharity->value . '/sitemapprocess.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunForCharity->value . 'enquiry' => [
            'driver' => 'daily',
            'path' => storage_path('logs/enquiry/' . SiteCodeEnum::RunForCharity->value . '/enquiry.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunForCharity->value . 'enquiryprocess' => [
            'driver' => 'daily',
            'path' => storage_path('logs/enquiry/' . SiteCodeEnum::RunForCharity->value . '/process.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunForCharity->value . 'processes' => [
            'driver' => 'daily',
            'path' => storage_path('logs/process/' . SiteCodeEnum::RunForCharity->value . '/processes.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunForCharity->value . 'stripe' => [
            'driver' => 'daily',
            'path' => storage_path('logs/stripe/' . SiteCodeEnum::RunForCharity->value .'/stripe.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunForCharity->value . 'stripepaymentintent' => [
            'driver' => 'daily',
            'path' => storage_path('logs/stripe/' . SiteCodeEnum::RunForCharity->value .'/paymentintent.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunForCharity->value . 'stripecharge' => [
            'driver' => 'daily',
            'path' => storage_path('logs/stripe/' . SiteCodeEnum::RunForCharity->value .'/charge.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunForCharity->value . 'stripepaymentmethod' => [
            'driver' => 'daily',
            'path' => storage_path('logs/stripe/' . SiteCodeEnum::RunForCharity->value .'/paymentmethod.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunForCharity->value . 'stripepaymentlink' => [
            'driver' => 'daily',
            'path' => storage_path('logs/stripe/' . SiteCodeEnum::RunForCharity->value .'/paymentlink.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunForCharity->value . 'command' => [
            'driver' => 'daily',
            'path' => storage_path('logs/commands/' . SiteCodeEnum::RunForCharity->value .'/commands.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunForCharity->value . 'mailexception' => [
            'driver' => 'daily',
            'path' => storage_path('logs/mail/' . SiteCodeEnum::RunForCharity->value .'/exception.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],






        SiteCodeEnum::RunThrough->value . 'admin' => [
            'driver' => 'daily',
            'path' => storage_path('logs/admin/' . SiteCodeEnum::RunThrough->value . '/admin.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunThrough->value . 'developer' => [
            'driver' => 'daily',
            'path' => storage_path('logs/developer/' . SiteCodeEnum::RunThrough->value . '/developer.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunThrough->value . 'adminanddeveloper' => [
            'driver' => 'daily',
            'path' => storage_path('logs/adminanddeveloper/' . SiteCodeEnum::RunThrough->value . '/adminanddeveloper.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunThrough->value . 'ldtfetch' => [
            'driver' => 'daily',
            'path' => storage_path('logs/ldt/fetch/' . SiteCodeEnum::RunThrough->value . '/fetch.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunThrough->value . 'ldtoffer' => [
            'driver' => 'daily',
            'path' => storage_path('logs/ldt/offer/' . SiteCodeEnum::RunThrough->value . '/offer.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunThrough->value . 'ldtofferprocess' => [
            'driver' => 'daily',
            'path' => storage_path('logs/ldt/offer/' . SiteCodeEnum::RunThrough->value . '/process.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunThrough->value . 'sitemap' => [
            'driver' => 'daily',
            'path' => storage_path('logs/sitemap/' . SiteCodeEnum::RunThrough->value . '/sitemap.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunThrough->value . 'sitemapprocess' => [
            'driver' => 'daily',
            'path' => storage_path('logs/sitemap/' . SiteCodeEnum::RunThrough->value . '/sitemapprocess.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunThrough->value . 'enquiry' => [
            'driver' => 'daily',
            'path' => storage_path('logs/enquiry/' . SiteCodeEnum::RunThrough->value . '/enquiry.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunThrough->value . 'enquiryprocess' => [
            'driver' => 'daily',
            'path' => storage_path('logs/enquiry/' . SiteCodeEnum::RunThrough->value . '/process.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunThrough->value . 'processes' => [
            'driver' => 'daily',
            'path' => storage_path('logs/process/' . SiteCodeEnum::RunThrough->value . '/processes.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunThrough->value . 'stripe' => [
            'driver' => 'daily',
            'path' => storage_path('logs/stripe/' . SiteCodeEnum::RunThrough->value .'/stripe.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunThrough->value . 'stripepaymentintent' => [
            'driver' => 'daily',
            'path' => storage_path('logs/stripe/' . SiteCodeEnum::RunThrough->value .'/paymentintent.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunThrough->value . 'stripecharge' => [
            'driver' => 'daily',
            'path' => storage_path('logs/stripe/' . SiteCodeEnum::RunThrough->value .'/charge.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunThrough->value . 'stripepaymentmethod' => [
            'driver' => 'daily',
            'path' => storage_path('logs/stripe/' . SiteCodeEnum::RunThrough->value .'/paymentmethod.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunThrough->value . 'stripepaymentlink' => [
            'driver' => 'daily',
            'path' => storage_path('logs/stripe/' . SiteCodeEnum::RunThrough->value .'/paymentlink.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunThrough->value . 'command' => [
            'driver' => 'daily',
            'path' => storage_path('logs/commands/' . SiteCodeEnum::RunThrough->value .'/commands.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunThrough->value . 'mailexception' => [
            'driver' => 'daily',
            'path' => storage_path('logs/mail/' . SiteCodeEnum::RunThrough->value .'/exception.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],





        SiteCodeEnum::RunningGrandPrix->value . 'admin' => [
            'driver' => 'daily',
            'path' => storage_path('logs/admin/' . SiteCodeEnum::RunningGrandPrix->value . '/admin.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunningGrandPrix->value . 'developer' => [
            'driver' => 'daily',
            'path' => storage_path('logs/developer/' . SiteCodeEnum::RunningGrandPrix->value . '/developer.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunningGrandPrix->value . 'adminanddeveloper' => [
            'driver' => 'daily',
            'path' => storage_path('logs/adminanddeveloper/' . SiteCodeEnum::RunningGrandPrix->value . '/adminanddeveloper.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunningGrandPrix->value . 'ldtfetch' => [
            'driver' => 'daily',
            'path' => storage_path('logs/ldt/fetch/' . SiteCodeEnum::RunningGrandPrix->value . '/fetch.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunningGrandPrix->value . 'ldtoffer' => [
            'driver' => 'daily',
            'path' => storage_path('logs/ldt/offer/' . SiteCodeEnum::RunningGrandPrix->value . '/offer.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunningGrandPrix->value . 'ldtofferprocess' => [
            'driver' => 'daily',
            'path' => storage_path('logs/ldt/offer/' . SiteCodeEnum::RunningGrandPrix->value . '/process.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunningGrandPrix->value . 'sitemap' => [
            'driver' => 'daily',
            'path' => storage_path('logs/sitemap/' . SiteCodeEnum::RunningGrandPrix->value . '/sitemap.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunningGrandPrix->value . 'sitemapprocess' => [
            'driver' => 'daily',
            'path' => storage_path('logs/sitemap/' . SiteCodeEnum::RunningGrandPrix->value . '/sitemapprocess.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunningGrandPrix->value . 'enquiry' => [
            'driver' => 'daily',
            'path' => storage_path('logs/enquiry/' . SiteCodeEnum::RunningGrandPrix->value . '/enquiry.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunningGrandPrix->value . 'enquiryprocess' => [
            'driver' => 'daily',
            'path' => storage_path('logs/enquiry/' . SiteCodeEnum::RunningGrandPrix->value . '/process.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunningGrandPrix->value . 'processes' => [
            'driver' => 'daily',
            'path' => storage_path('logs/process/' . SiteCodeEnum::RunningGrandPrix->value . '/processes.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunningGrandPrix->value . 'stripe' => [
            'driver' => 'daily',
            'path' => storage_path('logs/stripe/' . SiteCodeEnum::RunningGrandPrix->value .'/stripe.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunningGrandPrix->value . 'stripepaymentintent' => [
            'driver' => 'daily',
            'path' => storage_path('logs/stripe/' . SiteCodeEnum::RunningGrandPrix->value .'/paymentintent.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunningGrandPrix->value . 'stripecharge' => [
            'driver' => 'daily',
            'path' => storage_path('logs/stripe/' . SiteCodeEnum::RunningGrandPrix->value .'/charge.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunningGrandPrix->value . 'stripepaymentmethod' => [
            'driver' => 'daily',
            'path' => storage_path('logs/stripe/' . SiteCodeEnum::RunningGrandPrix->value .'/paymentmethod.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunningGrandPrix->value . 'stripepaymentlink' => [
            'driver' => 'daily',
            'path' => storage_path('logs/stripe/' . SiteCodeEnum::RunningGrandPrix->value .'/paymentlink.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunningGrandPrix->value . 'command' => [
            'driver' => 'daily',
            'path' => storage_path('logs/commands/' . SiteCodeEnum::RunningGrandPrix->value .'/commands.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],

        SiteCodeEnum::RunningGrandPrix->value . 'mailexception' => [
            'driver' => 'daily',
            'path' => storage_path('logs/mail/' . SiteCodeEnum::RunningGrandPrix->value .'/exception.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 5),
        ],
    ],
];
