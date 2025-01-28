<?php

return [
    'secret_key' => env('RUNFORCHARITY_STRIPE_SECRET_KEY'),

    'webhook' => [
        'secret' => [
            'payment_intent' => env('RUNFORCHARITY_STRIPE_WEBHOOK_SECRET_PAYMENT_INTENT'),
            'payment_method' => env('RUNFORCHARITY_STRIPE_WEBHOOK_SECRET_PAYMENT_METHOD'),
            'payment_link' => env('RUNFORCHARITY_STRIPE_WEBHOOK_SECRET_PAYMENT_LINK'),
            'charge' => env('RUNFORCHARITY_STRIPE_WEBHOOK_SECRET_CHARGE')
        ]
    ]
];
