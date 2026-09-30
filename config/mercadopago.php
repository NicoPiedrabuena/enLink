<?php

return [
    'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
    'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
    'sandbox' => env('MERCADOPAGO_SANDBOX', true),
    'base_url' => env('MERCADOPAGO_API_URL', 'https://api.mercadopago.com'),
];
