<?php

use App\Modules\Setting\Enums\SiteCodeEnum;

return [
    'x-client-key' => env('X_Client_Key'),

    /*
    | Blacklisted IP addresses
    */
    'ip_blacklist' => [

    ],

    SiteCodeEnum::Leicestershire10K->value => [
        'enquiries' => [
            'category_emails' => [
                'General Enquiry' => '',
                'Urgent Chip Timing' => 'RTnorth@racetimingsolutions.co.uk',
                'Company Partnership' => 'sam.williams@runthrough.co.uk',
                'Volunteering' => 'volunteer@runthrough.co.uk',
                'Charity Partnership' => 'andy.fish@runthrough.co.uk'
            ],
        ],
        'percent_change_days' => env('LEICESTERSHIRE10K_PERCENT_CHANGE_PERIOD_DAYS', 7)
    ],

    SiteCodeEnum::RunThrough->value => [
        'enquiries' => [
            'category_emails' => [
                'General' => '',
                'Press' => 'marketing@runthrough.co.uk',
                'Race Entries in the North' => 'north@runthrough.co.uk',
                'Race Entries in the Midlands' => 'midlands@runthrough.co.uk',
                'Partnership & Sponsorship Opportunities' => 'partners@runthrough.co.uk',
                'Volunteer Opportunities' => 'volunteer@runthrough.co.uk'
            ],
        ],
        'percent_change_days' => env('RUNTHROUGH_PERCENT_CHANGE_PERIOD_DAYS', 7)
    ],

    SiteCodeEnum::RunForCharity->value => [
        'enquiries' => [
            'category_emails' => [
                'General' => '',
                'Press' => 'marketing@runthrough.co.uk',
                'Race Entries in the North' => 'north@runthrough.co.uk',
                'Race Entries in the Midlands' => 'midlands@runthrough.co.uk',
                'Partnership & Sponsorship Opportunities' => 'partners@runthrough.co.uk',
                'Volunteer Opportunities' => 'volunteer@runthrough.co.uk'
            ],
        ],
        'percent_change_days' => env('RUNFORCHARITY_PERCENT_CHANGE_PERIOD_DAYS', 7)
    ],

    SiteCodeEnum::RunningGrandPrix->value => [
        'enquiries' => [
            'category_emails' => [
                'General' => '',
                'Press' => 'marketing@runthrough.co.uk',
                'Race Entries in the North' => 'north@runthrough.co.uk',
                'Race Entries in the Midlands' => 'midlands@runthrough.co.uk',
                'Partnership & Sponsorship Opportunities' => 'partners@runthrough.co.uk',
                'Volunteer Opportunities' => 'volunteer@runthrough.co.uk'
            ],
        ],
        'percent_change_days' => env('RUNNINGGRANDPRIX_PERCENT_CHANGE_PERIOD_DAYS', 7)
    ],

    'enquiries' => [
        SiteCodeEnum::RunThrough->value => [
            ['General' => 'info@runthrough.co.uk'],
            ['Press' => 'marketing@runthrough.co.uk'],
            ['Race Entries in the North' => 'north@runthrough.co.uk'],
            ['Race Entries in the Midlands' => 'midlands@runthrough.co.uk'],
            ['Partnership & Sponsorship Opportunities' => 'partners@runthrough.co.uk'],
            ['Volunteer Opportunities' => 'volunteer@runthrough.co.uk'],
        ],
        SiteCodeEnum::RunningGrandPrix->value => [
            ['General' => 'info@runthrough.co.uk'],
            ['Press' => 'marketing@runthrough.co.uk'],
            ['Race Entries in the North' => 'north@runthrough.co.uk'],
            ['Race Entries in the Midlands' => 'midlands@runthrough.co.uk'],
            ['Partnership & Sponsorship Opportunities' => 'partners@runthrough.co.uk'],
            ['Volunteer Opportunities' => 'volunteer@runthrough.co.uk'],
        ],
        SiteCodeEnum::Leicestershire10K->value => [
            ['General Enquiry' => 'midlands@runthrough.co.uk'],
            ['Urgent Chip Timing' => 'RTnorth@racetimingsolutions.co.uk'],
            ['Company Partnership' => 'sam.williams@runthrough.co.uk'],
            ['Volunteering' => 'volunteer@runthrough.co.uk'],
            ['Charity Partnership' => 'andy.fish@runthrough.co.uk'],
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | LDT Token
    |--------------------------------------------------------------------------
    |
    */

    'ldt_token' => env('LDT_TOKEN'),
    'ldt_checked_origin' => env('LDT_CHECKED_ORIGIN'),
    'ldt_endpoint' => env('LDT_ENDPOINT'),
    'ldt_single_participant_endpoint' => env('LDT_SINGLE_PARTICIPANT_ENDPOINT'),
    'ldt_checkout_url' => env('LDT_CHECKOUT_URL'),
    /*
    |--------------------------------------------------------------------------
    | TWITTER
    |--------------------------------------------------------------------------
    |
    */

    'twitter_bearer_token' => env('TWITTER_BEARER_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | DEFAULT PERIOD PER SITE TO USE WHEN COMPUTING PERCENTAGE CHANGE IN STATS
    |--------------------------------------------------------------------------
    |
    */
    'rthub' => [
        'percent_change_days' => env('RTHUB_PERCENT_CHANGE_PERIOD_DAYS', 7)
    ],
];
