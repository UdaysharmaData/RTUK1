<?php

return [
    'secret_key' => env('RUNNINGGRANDPRIX_STRIPE_SECRET_KEY'),

    'webhook' => [
        'secret' => [
            'payment_intent' => env('RUNNINGGRANDPRIX_STRIPE_WEBHOOK_SECRET_PAYMENT_INTENT'),
            'payment_method' => env('RUNNINGGRANDPRIX_STRIPE_WEBHOOK_SECRET_PAYMENT_METHOD'),
            'payment_link' => env('RUNNINGGRANDPRIX_STRIPE_WEBHOOK_SECRET_PAYMENT_LINK'),
            'charge' => env('RUNNINGGRANDPRIX_STRIPE_WEBHOOK_SECRET_CHARGE')
        ]
    ]
];
