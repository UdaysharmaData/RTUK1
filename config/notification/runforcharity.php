<?php

return [
    /*
    |---------------------------------------
    | Emails belonging to administrator accounts that are notifiable
    |---------------------------------------
    |
    */
    'notifiable_administrators' => env('RUNFORCHARITY_NOTIFIABLE_ADMINISTRATORS') ? explode(',', env('RUNFORCHARITY_NOTIFIABLE_ADMINISTRATORS')) : null,

    'top_executive' => [
        'name' => 'Marc Roby',
        'position' => 'CEO',
        'avatar' => config('app.images_path').'/notifications/marcroby.png'
    ]
];
