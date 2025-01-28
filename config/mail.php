<?php

use App\Modules\Setting\Enums\SiteCodeEnum;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send any email
    | messages sent by your application. Alternative mailers may be setup
    | and used as needed; however, this mailer will be used by default.
    |
    */

    'default' => env('MAIL_MAILER', 'smtp'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers to be used while
    | sending an e-mail. You will specify which one you are using for your
    | mailers below. You are free to add additional mailers as required.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses",
    |            "postmark", "log", "array", "failover"
    |
    */

    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('MAIL_PORT', 587),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
        ],

        SiteCodeEnum::RunForCharity->value => [
            'transport' => 'smtp',
            'host' => env('RUNFORCHARITY_MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('RUNFORCHARITY_MAIL_PORT', 587),
            'encryption' => env('RUNFORCHARITY_MAIL_ENCRYPTION', 'tls'),
            'username' => env('RUNFORCHARITY_MAIL_USERNAME'),
            'password' => env('RUNFORCHARITY_MAIL_PASSWORD'),
            'timeout' => null,
        ],

        SiteCodeEnum::RunThrough->value => [
            'transport' => 'smtp',
            'host' => env('RUNTHROUGH_MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('RUNTHROUGH_MAIL_PORT', 587),
            'encryption' => env('RUNTHROUGH_MAIL_ENCRYPTION', 'tls'),
            'username' => env('RUNTHROUGH_MAIL_USERNAME'),
            'password' => env('RUNTHROUGH_MAIL_PASSWORD'),
            'timeout' => null,
        ],

        SiteCodeEnum::RunningGrandPrix->value => [
            'transport' => 'smtp',
            'host' => env('RUNNINGGRANDPRIX_MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('RUNNINGGRANDPRIX_MAIL_PORT', 587),
            'encryption' => env('RUNNINGGRANDPRIX_MAIL_ENCRYPTION', 'tls'),
            'username' => env('RUNNINGGRANDPRIX_MAIL_USERNAME'),
            'password' => env('RUNNINGGRANDPRIX_MAIL_PASSWORD'),
            'timeout' => null,
        ],

        SiteCodeEnum::Leicestershire10K->value => [
            'transport' => 'smtp',
            'host' => env('LEICESTERSHIRE10K_MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('LEICESTERSHIRE10K_MAIL_PORT', 587),
            'encryption' => env('LEICESTERSHIRE10K_MAIL_ENCRYPTION', 'tls'),
            'username' => env('LEICESTERSHIRE10K_MAIL_USERNAME'),
            'password' => env('LEICESTERSHIRE10K_MAIL_PASSWORD'),
            'timeout' => null,
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'mailgun' => [
            'transport' => 'mailgun',
        ],

        'postmark' => [
            'transport' => 'postmark',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -t -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address & Other Addreses
    |--------------------------------------------------------------------------
    |
    | You may wish for all e-mails sent by your application to be sent from
    | the same address. Here, you may specify a name and address that is
    | used globally for all e-mails that are sent by your application.
    | Addresses to whom some special emails are to be sent are defined here too.
    |
    */


    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'mailer@runthrough.co.uk'),
        'name' => env('MAIL_FROM_NAME', 'RunThrough'),
    ],
    
    /*'admin_email' => [
        'address' => env('ADMIN_FROM_ADDRESS', 'dominic@royand.co'),
        'name' => env('ADMIN_FROM_NAME', 'Dominc Royand'),
    ],

    'cp_email' => [
        'address' => env('CP_FROM_ADDRESS', 'charities@sportforcharity.com'),
        'name' => env('CP_FROM_NAME', 'Charities - Sport for Charity'),
    ],

    'bfc_email' => [
        'address' => env('BFC_FROM_ADDRESS', 'business@sportforcharity.com'),
        'name' => env('BFC_FROM_NAME', 'Business - Sport for Charity'),
    ],

    'mu_email' => [
        'address' => env('MU_FROM_ADDRESS', 'monthlyupdate@sportforcharity.com'),
        'name' => env('MU_FROM_NAME', 'Monthly Update - Sport for Charity'),
    ],

    'rr_email' => [
        'address' => env('RR_FROM_ADDRESS', 'registrationreminder@sportforcharity.com'),
        'name' => env('RR_FROM_NAME', 'Registration Reminder - Sport for Charity'),
    ],

    'rrs_email' => [
        'address' => env('RRs_FROM_ADDRESS', 'portal@runningrankings.com'),
        'name' => env('RRs_FROM_NAME', 'Running Rankings'),
    ],

    'rrs_support_email' => [
        'address' => env('RRs_SUPPORT_ADDRESS', 'support@runningrankings.com'),
        'name' => env('RRs_SUPPORT_NAME', 'Running Rankings Support Name'),
    ],

    'rrs_admin_email' => [
        'address' => env('RRs_ADMIN_ADDRESS', 'admin@runningrankings.com'),
        'name' => env('RRs_ADMIN_NAME', 'Running Rankings'),
    ],

    'vmm_email' => [
        'address' => env('VMM_ADDRESS', 'portal@virtualmarathonseries.com'),
        'name' => env('VMM_NAME', 'Virtual Marathon Series'),
    ],

    'vmm_reg_email' => [
        'address' => env('VMM_REG_FROM_ADDRESS', 'registrations@virtualmarathonseries.com'),
        'name' => env('VMM_REG_FROM_NAME', 'Virtual Marathon Series Registrations'),
    ],

    'ldt_email' => [
        'address' => env('LDT_FROM_ADDRESS', 'archie@letsdothis.com'),
        'name' => env('LDT_NAME', 'Let\'s Do This Name'),
    ],

    'ldt_em_email' => [
        'address' => env('LDT_EM_FROM_ADDRESS', 'constantine@letsdothis.com'),
        'name' => env('LDT_EM_FROM_NAME', 'Let\'s Do This Event Manager'),
    ],

    'info_email' => [
        'address' => env('INFO_ADDRESS', 'info@runforcharity.com'),
        'name' => env('INFO_NAME', 'Running Rankings'),
    ],

    'vmm_email_2' => [
        'address' => env('VMM_ADDRESS', 'sfc@virtualmarathonseries.com'),
        'name' => env('VMM_NAME', 'Virtual Marathon Series'),
    ],

    'default_charity_email' => [
        'address' => env('DEFAULT_CHARITY_FROM_ADDRESS', 'default@charity.com'),
        'name' => env('DEFAULT_CHARITY_FROM_NAME', 'Default Charity'),
    ],

    'vmm_support_email' => [
        'address' => env('VMM_SUPPORT_FROM_ADDRESS', 'support@virtualmarathonseries.com'),
        'name' => env('VMM_SUPPORT_FROM_NAME', 'Virtual Marathon Series'),
    ],*/

    /*
    |--------------------------------------------------------------------------
    | Markdown Mail Settings
    |--------------------------------------------------------------------------
    |
    | If you are using Markdown based email rendering, you may configure your
    | theme and component paths here, allowing you to customize the design
    | of the emails. Or, you may simply stick with the Laravel defaults!
    |
    */

    'markdown' => [
        'theme' => 'default',

        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],

];
