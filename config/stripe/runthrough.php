<?php

return [
    'secret_key' => env('RUNTHROUGH_STRIPE_SECRET_KEY'),

    'webhook' => [
        'secret' => [
            'payment_intent' => env('RUNTHROUGH_STRIPE_WEBHOOK_SECRET_PAYMENT_INTENT'),
            'payment_method' => env('RUNTHROUGH_STRIPE_WEBHOOK_SECRET_PAYMENT_METHOD'),
            'payment_link' => env('RUNTHROUGH_STRIPE_WEBHOOK_SECRET_PAYMENT_LINK'),
            'charge' => env('RUNTHROUGH_STRIPE_WEBHOOK_SECRET_CHARGE')
        ]
    ]
];
