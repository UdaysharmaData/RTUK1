<?php

return [

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
        'address' => env('LEICESTERSHIRE10K_MAIL_FROM_ADDRESS', 'mailer@leicestershire10k.com'),
        'name' => env('LEICESTERSHIRE10K_MAIL_FROM_NAME', 'Running Grand Prix'),
    ],

    'noreply' => [
        'address' => env('LEICESTERSHIRE10K_MAIL_NOREPLY_FROM_ADDRESS', 'noreply@leicestershire10k.com'),
        'name' => env('LEICESTERSHIRE10K_MAIL_NOREPLY_FROM_NAME', 'Running Grand Prix'),
    ],

    'administrator' => [
        'address' => env('LEICESTERSHIRE10K_MAIL_ADMINISTRATOR_FROM_ADDRESS', 'matt@leicestershire10k.com'),
        'name' => env('LEICESTERSHIRE10K_MAIL_ADMINISTRATOR_FROM_NAME', 'Matt Wood'),
    ],
];
