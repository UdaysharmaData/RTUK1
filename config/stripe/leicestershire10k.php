<?php

return [
    'secret_key' => env('LEICESTERSHIRE10K_STRIPE_SECRET_KEY'),

    'webhook' => [
        'secret' => [
            'payment_intent' => env('LEICESTERSHIRE10K_STRIPE_WEBHOOK_SECRET_PAYMENT_INTENT'),
            'payment_method' => env('LEICESTERSHIRE10K_STRIPE_WEBHOOK_SECRET_PAYMENT_METHOD'),
            'payment_link' => env('LEICESTERSHIRE10K_STRIPE_WEBHOOK_SECRET_PAYMENT_LINK'),
            'charge' => env('LEICESTERSHIRE10K_STRIPE_WEBHOOK_SECRET_CHARGE')
        ]
    ]
];
