<?php

return [
    'acknowledgements' => [
        'driver' => env('CP_NOTIFICATIONS_DRIVER', 'auto'),
        'file_path' => storage_path('statamic/cp-notifications'),
    ],

    'enforcement' => env('CP_NOTIFICATIONS_ENFORCEMENT', 'strict'),

    'retention' => [
        'inbox_days' => env('CP_NOTIFICATIONS_INBOX_DAYS'),
    ],

    'nudge' => [
        'from_address' => env('CP_NOTIFICATIONS_FROM_ADDRESS'),
    ],
];
