<?php

return [
    'publication_credit_cost' => (int) env('ENLINK_PUBLICATION_CREDIT_COST', 1),
    'starter_package_name' => env('ENLINK_STARTER_PACKAGE_NAME', 'Pack inicial'),
    'analytics_retention_days' => (int) env('ENLINK_ANALYTICS_RETENTION_DAYS', 90),
    'webhook_retention_days' => (int) env('ENLINK_WEBHOOK_RETENTION_DAYS', 180),
    'payment_reconciliation_hours' => (int) env('ENLINK_PAYMENT_RECONCILIATION_HOURS', 168),
    'trusted_proxies' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TRUSTED_PROXIES', '')),
    ))),
    'admin' => [
        'email' => env('ENLINK_ADMIN_EMAIL'),
        'password' => env('ENLINK_ADMIN_PASSWORD'),
    ],
];
