<?php

declare(strict_types=1);

return [
    'avaya' => [
        'base_url' => env('AVAYA_URL_BASE', ''),
        'api_key' => env('AVAYA_API_KEY', ''),
        'timeout' => (int) env('AVAYA_TIMEOUT', 15),
    ],
];
