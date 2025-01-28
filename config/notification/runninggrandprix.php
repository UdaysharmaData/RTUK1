<?php

return [
    /*
    |---------------------------------------
    | Emails belonging to administrator accounts that are notifiable
    |---------------------------------------
    |
    */
    'notifiable_administrators' => env('RUNNINGGRANDPRIX_NOTIFIABLE_ADMINISTRATORS') ? explode(',', env('RUNNINGGRANDPRIX_NOTIFIABLE_ADMINISTRATORS')) : null,

    'top_executive' => [
        'name' => 'Matt Wood',
        'position' => 'Founder',
        'avatar' => config('app.images_path').'/notifications/mattwood.jpeg'
    ]
];
