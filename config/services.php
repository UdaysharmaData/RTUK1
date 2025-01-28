<?php

use App\Modules\Setting\Enums\SiteCodeEnum;

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'table_name' => env('AWS_DYNAMODB_REDIRECT_TABLE'),
    ],

    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'public' => env('STRIPE_PUBLIC'),
    ],

    'rtuk_mail_credentials' => [
        'RUNTHROUGH_MAIL_HOST' => env('RUNTHROUGH_MAIL_HOST'),
        'RUNTHROUGH_MAIL_PORT' => env('RUNTHROUGH_MAIL_PORT'),
        'RUNTHROUGH_MAIL_ENCRYPTION' => env('RUNTHROUGH_MAIL_ENCRYPTION'),
        'RUNTHROUGH_MAIL_USERNAME' => env('RUNTHROUGH_MAIL_USERNAME'),
        'RUNTHROUGH_MAIL_PASSWORD' => env('RUNTHROUGH_MAIL_PASSWORD'),
    ],

    SiteCodeEnum::RunThrough->value => [
        'facebook' => [
            'client_id' => env('RUNTHROUGH_FACEBOOK_CLIENT_ID'),
            'client_secret' => env('RUNTHROUGH_FACEBOOK_CLIENT_SECRET'),
            'redirect' => env('RUNTHROUGH_FACEBOOK_CLIENT_REDIRECT_URL'),
        ],

        'twitter' => [
            'client_id' => env('RUNTHROUGH_TWITTER_CLIENT_ID'),
            'client_secret' => env('RUNTHROUGH_TWITTER_CLIENT_SECRET'),
            'redirect' => env('RUNTHROUGH_TWITTER_CLIENT_REDIRECT_URL'),
        ],

        'google' => [
            'client_id' => env('RUNTHROUGH_GOOGLE_CLIENT_ID'),
            'client_secret' => env('RUNTHROUGH_GOOGLE_CLIENT_SECRET'),
            'redirect' => env('RUNTHROUGH_GOOGLE_CLIENT_REDIRECT_URL'),
        ],

        'twitter-oauth-2' => [
            'client_id' => env('RUNTHROUGH_TWITTER_OAUTH_2_CLIENT_ID'),
            'client_secret' => env('RUNTHROUGH_TWITTER_OAUTH_2_CLIENT_SECRET'),
            'redirect' => env('RUNTHROUGH_TWITTER_OAUTH_2_CLIENT_REDIRECT_URL'),
        ],

        'linkedin' => [
            'client_id' => env('RUNTHROUGH_LINKEDIN_CLIENT_ID'),
            'client_secret' => env('RUNTHROUGH_LINKEDIN_CLIENT_SECRET'),
            'redirect' => env('RUNTHROUGH_LINKEDIN_CLIENT_REDIRECT_URL'),
        ],

        'supported_socials_sources' => [
            'runthrough.runthroughhub.com/auth/login',
            'runthrough.runthroughhub.com/auth/register',
            'portal.runthroughhub.com/auth/login',
            'portal.runthroughhub.com/auth/register',
            'portal.runthrough.co.uk/auth/login',
            'portal.runthrough.co.uk/auth/register',
            'runthrough.co.uk/auth/login',
            'runthrough.co.uk/auth/register',
        ],
    ],

    SiteCodeEnum::RunningGrandPrix->value => [
        'facebook' => [
            'client_id' => env('RUNNINGGRANDPRIX_FACEBOOK_CLIENT_ID'),
            'client_secret' => env('RUNNINGGRANDPRIX_FACEBOOK_CLIENT_SECRET'),
            'redirect' => env('RUNNINGGRANDPRIX_FACEBOOK_CLIENT_REDIRECT_URL'),
        ],

        'twitter' => [
            'client_id' => env('RUNNINGGRANDPRIX_TWITTER_CLIENT_ID'),
            'client_secret' => env('RUNNINGGRANDPRIX_TWITTER_CLIENT_SECRET'),
            'redirect' => env('RUNNINGGRANDPRIX_TWITTER_CLIENT_REDIRECT_URL'),
        ],

        'google' => [
            'client_id' => env('RUNNINGGRANDPRIX_GOOGLE_CLIENT_ID'),
            'client_secret' => env('RUNNINGGRANDPRIX_GOOGLE_CLIENT_SECRET'),
            'redirect' => env('RUNNINGGRANDPRIX_GOOGLE_CLIENT_REDIRECT_URL'),
        ],

        'twitter-oauth-2' => [
            'client_id' => env('RUNNINGGRANDPRIX_TWITTER_OAUTH_2_CLIENT_ID'),
            'client_secret' => env('RUNNINGGRANDPRIX_TWITTER_OAUTH_2_CLIENT_SECRET'),
            'redirect' => env('RUNNINGGRANDPRIX_TWITTER_OAUTH_2_CLIENT_REDIRECT_URL'),
        ],

        'linkedin' => [
            'client_id' => env('RUNNINGGRANDPRIX_LINKEDIN_CLIENT_ID'),
            'client_secret' => env('RUNNINGGRANDPRIX_LINKEDIN_CLIENT_SECRET'),
            'redirect' => env('RUNNINGGRANDPRIX_LINKEDIN_CLIENT_REDIRECT_URL'),
        ],

        'supported_socials_sources' => [
            'runthrough.runthroughhub.com/auth/login',
            'runthrough.runthroughhub.com/auth/register',
            'portal.runthroughhub.com/auth/login',
            'portal.runthroughhub.com/auth/register',
            'portal.runthrough.co.uk/auth/login',
            'portal.runthrough.co.uk/auth/register',
            'runthrough.co.uk/auth/login',
            'runthrough.co.uk/auth/register',
        ],
    ],

    SiteCodeEnum::RunThroughHub->value => [
        'github' => [
            'client_id' => env('RTHUB_GITHUB_CLIENT_ID'),
            'client_secret' => env('RTHUB_GITHUB_CLIENT_SECRET'),
            'redirect' => env('RTHUB_GITHUB_CLIENT_REDIRECT_URL'),
        ],

        'facebook' => [
            'client_id' => env('RTHUB_FACEBOOK_CLIENT_ID'),
            'client_secret' => env('RTHUB_FACEBOOK_CLIENT_SECRET'),
            'redirect' => env('RTHUB_FACEBOOK_CLIENT_REDIRECT_URL'),
        ],

        'twitter' => [
            'client_id' => env('RTHUB_TWITTER_CLIENT_ID'),
            'client_secret' => env('RTHUB_TWITTER_CLIENT_SECRET'),
            'redirect' => env('RTHUB_TWITTER_CLIENT_REDIRECT_URL'),
        ],

        'google' => [
            'client_id' => env('RTHUB_GOOGLE_CLIENT_ID'),
            'client_secret' => env('RTHUB_GOOGLE_CLIENT_SECRET'),
            'redirect' => env('RTHUB_GOOGLE_CLIENT_REDIRECT_URL'),
        ],

        'twitter-oauth-2' => [
            'client_id' => env('RTHUB_TWITTER_OAUTH_2_CLIENT_ID'),
            'client_secret' => env('RTHUB_TWITTER_OAUTH_2_CLIENT_SECRET'),
            'redirect' => env('RTHUB_TWITTER_OAUTH_2_CLIENT_REDIRECT_URL'),
        ],

        'linkedin' => [
            'client_id' => env('RTHUB_LINKEDIN_CLIENT_ID'),
            'client_secret' => env('RTHUB_LINKEDIN_CLIENT_SECRET'),
            'redirect' => env('RTHUB_LINKEDIN_CLIENT_REDIRECT_URL'),
        ],
        'supported_socials_sources' => [
            'runthrough.runthroughhub.com/auth/login',
            'runthrough.runthroughhub.com/auth/register',
            'portal.runthroughhub.com/auth/login',
            'portal.runthroughhub.com/auth/register',
            'portal.runthrough.co.uk/auth/login',
            'portal.runthrough.co.uk/auth/register',
            'runthrough.co.uk/auth/login',
            'runthrough.co.uk/auth/register',
        ],
    ],

    SiteCodeEnum::RunForCharity->value => [
        'github' => [
            'client_id' => env('RFC_GITHUB_CLIENT_ID'),
            'client_secret' => env('RFC_GITHUB_CLIENT_SECRET'),
            'redirect' => env('RFC_GITHUB_CLIENT_REDIRECT_URL'),
        ],

        'facebook' => [
            'client_id' => env('RFC_FACEBOOK_CLIENT_ID'),
            'client_secret' => env('RFC_FACEBOOK_CLIENT_SECRET'),
            'redirect' => env('RFC_FACEBOOK_CLIENT_REDIRECT_URL'),
        ],

        'twitter' => [
            'client_id' => env('RFC_TWITTER_CLIENT_ID'),
            'client_secret' => env('RFC_TWITTER_CLIENT_SECRET'),
            'redirect' => env('RFC_TWITTER_CLIENT_REDIRECT_URL'),
        ],

        'google' => [
            'client_id' => env('RFC_GOOGLE_CLIENT_ID'),
            'client_secret' => env('RFC_GOOGLE_CLIENT_SECRET'),
            'redirect' => env('RFC_GOOGLE_CLIENT_REDIRECT_URL'),
        ],

        'twitter-oauth-2' => [
            'client_id' => env('RFC_TWITTER_OAUTH_2_CLIENT_ID'),
            'client_secret' => env('RFC_TWITTER_OAUTH_2_CLIENT_SECRET'),
            'redirect' => env('RFC_TWITTER_OAUTH_2_CLIENT_REDIRECT_URL'),
        ],

        'linkedin' => [
            'client_id' => env('RFC_LINKEDIN_CLIENT_ID'),
            'client_secret' => env('RFC_LINKEDIN_CLIENT_SECRET'),
            'redirect' => env('RFC_LINKEDIN_CLIENT_REDIRECT_URL'),
        ],
        'supported_socials_sources' => [
            'runthrough.runthroughhub.com/auth/login',
            'runthrough.runthroughhub.com/auth/register',
            'portal.runthroughhub.com/auth/login',
            'portal.runthroughhub.com/auth/register',
            'portal.runthrough.co.uk/auth/login',
            'portal.runthrough.co.uk/auth/register',
            'runthrough.co.uk/auth/login',
            'runthrough.co.uk/auth/register',
        ],
    ],

    SiteCodeEnum::SportForCharity->value => [
        'github' => [
            'client_id' => env('SFC_GITHUB_CLIENT_ID'),
            'client_secret' => env('SFC_GITHUB_CLIENT_SECRET'),
            'redirect' => env('SFC_GITHUB_CLIENT_REDIRECT_URL'),
        ],

        'facebook' => [
            'client_id' => env('SFC_FACEBOOK_CLIENT_ID'),
            'client_secret' => env('SFC_FACEBOOK_CLIENT_SECRET'),
            'redirect' => env('SFC_FACEBOOK_CLIENT_REDIRECT_URL'),
        ],

        'twitter' => [
            'client_id' => env('SFC_TWITTER_CLIENT_ID'),
            'client_secret' => env('SFC_TWITTER_CLIENT_SECRET'),
            'redirect' => env('SFC_TWITTER_CLIENT_REDIRECT_URL'),
        ],

        'google' => [
            'client_id' => env('SFC_GOOGLE_CLIENT_ID'),
            'client_secret' => env('SFC_GOOGLE_CLIENT_SECRET'),
            'redirect' => env('SFC_GOOGLE_CLIENT_REDIRECT_URL'),
        ],

        'twitter-oauth-2' => [
            'client_id' => env('SFC_TWITTER_OAUTH_2_CLIENT_ID'),
            'client_secret' => env('SFC_TWITTER_OAUTH_2_CLIENT_SECRET'),
            'redirect' => env('SFC_TWITTER_OAUTH_2_CLIENT_REDIRECT_URL'),
        ],

        'linkedin' => [
            'client_id' => env('SFC_LINKEDIN_CLIENT_ID'),
            'client_secret' => env('SFC_LINKEDIN_CLIENT_SECRET'),
            'redirect' => env('SFC_LINKEDIN_CLIENT_REDIRECT_URL'),
        ],
        'supported_socials_sources' => [
            'runthrough.runthroughhub.com/auth/login',
            'runthrough.runthroughhub.com/auth/register',
            'portal.runthroughhub.com/auth/login',
            'portal.runthroughhub.com/auth/register',
            'portal.runthrough.co.uk/auth/login',
            'portal.runthrough.co.uk/auth/register',
            'runthrough.co.uk/auth/login',
            'runthrough.co.uk/auth/register',
        ],
    ],

    SiteCodeEnum::VirtualMarathonSeries->value => [
        'github' => [
            'client_id' => env('VMS_GITHUB_CLIENT_ID'),
            'client_secret' => env('VMS_GITHUB_CLIENT_SECRET'),
            'redirect' => env('VMS_GITHUB_CLIENT_REDIRECT_URL'),
        ],

        'facebook' => [
            'client_id' => env('VMS_FACEBOOK_CLIENT_ID'),
            'client_secret' => env('VMS_FACEBOOK_CLIENT_SECRET'),
            'redirect' => env('VMS_FACEBOOK_CLIENT_REDIRECT_URL'),
        ],

        'twitter' => [
            'client_id' => env('VMS_TWITTER_CLIENT_ID'),
            'client_secret' => env('VMS_TWITTER_CLIENT_SECRET'),
            'redirect' => env('VMS_TWITTER_CLIENT_REDIRECT_URL'),
        ],

        'google' => [
            'client_id' => env('VMS_GOOGLE_CLIENT_ID'),
            'client_secret' => env('VMS_GOOGLE_CLIENT_SECRET'),
            'redirect' => env('VMS_GOOGLE_CLIENT_REDIRECT_URL'),
        ],

        'twitter-oauth-2' => [
            'client_id' => env('VMS_TWITTER_OAUTH_2_CLIENT_ID'),
            'client_secret' => env('VMS_TWITTER_OAUTH_2_CLIENT_SECRET'),
            'redirect' => env('VMS_TWITTER_OAUTH_2_CLIENT_REDIRECT_URL'),
        ],

        'linkedin' => [
            'client_id' => env('VMS_LINKEDIN_CLIENT_ID'),
            'client_secret' => env('VMS_LINKEDIN_CLIENT_SECRET'),
            'redirect' => env('VMS_LINKEDIN_CLIENT_REDIRECT_URL'),
        ],
        'supported_socials_sources' => [

        ],
    ],

    'twilio' => [
        'environment' => env('TWILIO_ENVIRONMENT', 'live'),
        'live' => [
            'sid' => env('TWILIO_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),
            'sender_id' => env('TWILIO_SENDER_ID')
        ],
        'test' => [
            'sid' => env('TWILIO_SID_TEST'),
            'auth_token' => env('TWILIO_AUTH_TOKEN_TEST'),
            'sender_id' => env('TWILIO_SENDER_ID_TEST')
        ],
    ],
    'recaptcha' => [
        'url' => 'https://www.google.com/recaptcha/api/siteverify',
        'secret' => env('RECAPTCHA_SECRET'),
    ]
];
