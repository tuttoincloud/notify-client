<?php

return [
    'base_url' => env('NOTIFY_BASE_URL', 'https://tuttoincloud.online'),
    'token'    => env('NOTIFY_TOKEN'),
    'timeout'  => env('NOTIFY_TIMEOUT', 10),
    'outbox'   => [
        'enabled'      => env('NOTIFY_OUTBOX', true),
        'max_attempts' => 5,
        'queue'        => env('NOTIFY_OUTBOX_QUEUE', 'default'),
    ],
];
