<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Data Exchange Service Config
    |--------------------------------------------------------------------------
    |
    | Here you may configure the importer information for each data source.
    |
    */

    'sources' => [
        'api' => [
            'url' => [
                'hostname' => env('IMPORTER_API_BASE_URL'),
                'path' => '/relative-path', // todo: update
            ],
            'retry' => [
                'times' => 3, // no of retries allowed if error occurs on API server request
                'sleep_milliseconds' => 100,
            ],
        ],
    ],
];
