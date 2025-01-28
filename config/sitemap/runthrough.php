<?php

use App\Models\City;
use App\Models\Page;
use App\Models\Venue;
use App\Models\Region;
use App\Models\Upload;
use App\Models\Combination;
use App\Modules\Event\Models\Event;
use App\Modules\Partner\Models\Partner;
use App\Modules\Event\Models\EventCategory;

return [
    /*
    |--------------------------------------------------------------------------
    | The path to the directory where the site's sitemap is located on disk
    |--------------------------------------------------------------------------
    |
    */
    'path' => env('RUNTHROUGH_SITEMAP_PATH'), // Absolute path to the file eg /Users/mesmer/Desktop/Sitemap

    'frontend_path' => env('RUNTHROUGH_SITEMAP_FRONTEND_PATH'), // The path where the file is to be saved on the server. This was added due to the way sitemap is setup on our aws hosting. eg /Users/mesmer/Desktop/Sitemap

    /*
    |--------------------------------------------------------------------------
    | How often specific pages are being updated
    |--------------------------------------------------------------------------
    |
    */
    'change_freq' => 'weekly',

    /*
    |--------------------------------------------------------------------------
    | 
    |--------------------------------------------------------------------------
    |
    */
    'priority' => '0.7',

    /*
    |----------------------------------------------------------------------------------
    | The update and regenerate frequencies
    |----------------------------------------------------------------------------------
    |
    */
    'update_frequency' => env('RUNTHROUGH_SITEMAP_UPDATE_FREQUENCY', 'daily'),

    'regenerate_frequency' => env('RUNTHROUGH_SITEMAP_REGENERATE_FREQUENCY', 'monthly'),

    /*
    |----------------------------------------------------------------------------------
    | The entities (associated with the site) for which the sitemap should be generated
    |----------------------------------------------------------------------------------
    |
    */
    'entities' => [
        [
            'model' => City::class,
            'file_name' => 'cities.xml',
        ],
        [
            'model' => Event::class,
            'file_name' => 'events.xml',
        ], 
        [
            'model' => EventCategory::class,
            'file_name' => 'event-categories.xml',
        ],
        [
            'model' => Page::class,
            'file_name' => 'pages.xml',
        ],
        [
            'model' => Partner::class,
            'file_name' => 'partners.xml',
        ],
        [
            'model' => Region::class,
            'file_name' => 'regions.xml',
        ],
        [
            'model' => Venue::class,
            'file_name' => 'venues.xml',
        ],
        [
            'model' => Combination::class,
            'file_name' => 'combinations.xml',
        ],
        [
            'model' => Upload::class,
            'file_name' => 'uploads.xml',
        ]
    ]
];