<?php

use App\Models\City;
use App\Modules\Charity\Models\Charity;
use App\Modules\Charity\Models\CharityCategory;

return [
    /*
    |--------------------------------------------------------------------------
    | The path to the directory where the site's sitemap is located on disk
    |--------------------------------------------------------------------------
    |
    */
    'path' => env('SITEMAP_PATH'), // Absolute path to the file eg /Users/mesmer/Desktop/Sitemap

    'frontend_path' => env('SITEMAP_FRONTEND_PATH'), // The path where the file is to be saved on the server. This was added due to the way sitemap is setup on our aws hosting. eg /Users/mesmer/Desktop/Sitemap

    /*
    |--------------------------------------------------------------------------
    | The value of the sitemap frequency property
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
    'update_frequency' => env('SITEMAP_UPDATE_FREQUENCY', 'daily'),

    'regenerate_frequency' => env('SITEMAP_REGENERATE_FREQUENCY', 'monthly'),

    /*
    |----------------------------------------------------------------------------------
    | The entities (associated with the site) for which the sitemap should be generated
    |----------------------------------------------------------------------------------
    |
    */
    'entities' => [
        [
            'model' => Charity::class,
            'file_name' => 'charities.xml',
        ],
        [
            'model' => CharityCategory::class,
            'file_name' => 'charity-categories.xml',
        ],
        [
            'model' => City::class,
            'file_name' => 'cities.xml',
        ],
    ]
];