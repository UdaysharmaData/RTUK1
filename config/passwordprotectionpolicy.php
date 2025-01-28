<?php

return [
    /*
    | How many passwords to track in history
    */
    'keep' => 10,

    /*
    | The models to be observed on and your password column name.
    */
    'observe' => [
        'model' => \App\Modules\User\Models\User::class,
        'column' => 'password',
    ],

    /*
    | Minimum password character length allowed.
    */
    'min_password_length' => 8,

    /*
    | Maximum password age in days
    */
    'max_password_age' => [
        'user' => 90,
        'admin' => 180
    ],

    /*
    | When should a reminder be sent for password expiration/update
    */
    'days_before_expiry_reminder' => 10,

    /*
    | min length allowed when creating a passphrase
    */
    'min_passphrase_length' => 15,
    'passphrase_cache_mins' => 60
];
