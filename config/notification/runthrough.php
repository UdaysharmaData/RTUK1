<?php

return [
    /*
    |---------------------------------------
    | Emails belonging to administrator accounts that are notifiable
    |---------------------------------------
    |
    */
    'notifiable_administrators' => env('RUNTHROUGH_NOTIFIABLE_ADMINISTRATORS') ? explode(',', env('RUNTHROUGH_NOTIFIABLE_ADMINISTRATORS')) : null,

    'top_executive' => [
        'name' => 'Matt Wood',
        'position' => 'Founder',
        'avatar' => config('app.images_path').'/notifications/mattwood.jpeg'
    ]
];
