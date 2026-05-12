<?php

declare(strict_types=1);

use Laravel\Pulse\Recorders;

return [

    /*
    |--------------------------------------------------------------------------
    | Pulse Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Pulse will be accessible from. If this
    | setting is null, Pulse will reside under the same domain as the
    | application. Otherwise, this value will serve as the subdomain.
    |
    */

    'domain' => env('PULSE_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Pulse Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Pulse will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the paths of its internal API that aren't exposed to users.
    |
    */

    'path' => env('PULSE_PATH', 'pulse'),

    /*
    |--------------------------------------------------------------------------
    | Pulse Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will get attached onto each Pulse route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => ['web', 'role:admin'],

    /*
    |--------------------------------------------------------------------------
    | Pulse Ingest Configuration
    |--------------------------------------------------------------------------
    |
    | The following options configure how Pulse ingests incoming events from
    | your application. You may choose to ingest events synchronously or
    | using a queue to improve your application's response time.
    |
    */

    'ingest' => [
        'driver' => env('PULSE_INGEST_DRIVER', 'redis'),

        'buffer' => 1000, // Procesamiento por lotes para reducir I/O

        'trim' => [
            'keep' => '7 days',
            'lottery' => [1, 1000],
        ],

        'redis' => [
            'connection' => env('PULSE_REDIS_CONNECTION'),
            'chunk' => 1000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pulse Recorders
    |--------------------------------------------------------------------------
    |
    | The following options configure which recorders will be used by Pulse
    | to capture information about your application's performance. You
    | may add or remove recorders from this list as you see fit.
    |
    */

    'recorders' => [
        Recorders\CacheInteractions::class => [
            'enabled' => true,
            'sample_rate' => 1,
            'ignore' => [
                '/^laravel:pulse:/',
            ],
        ],

        Recorders\Exceptions::class => [
            'enabled' => true,
            'sample_rate' => 1,
            'location' => true,
            'ignore' => [
                // '/^App\\Exceptions\\IgnoredException$/',
            ],
        ],

        Recorders\Queues::class => [
            'enabled' => true,
            'sample_rate' => 1,
            'ignore' => [
                // '/^App\\Jobs\\IgnoredJob$/',
            ],
        ],

        Recorders\SlowJobs::class => [
            'enabled' => true,
            'sample_rate' => 1,
            'threshold' => 1000,
            'ignore' => [
                // '/^App\\Jobs\\IgnoredJob$/',
            ],
        ],

        Recorders\SlowOutgoingRequests::class => [
            'enabled' => true,
            'sample_rate' => 1,
            'threshold' => 1000,
            'ignore' => [
                // '#^http://example.com/api$#',
            ],
        ],

        Recorders\SlowQueries::class => [
            'enabled' => true,
            'sample_rate' => 1,
            'threshold' => 500, // RNF-01: Detectar peticiones > 500ms
            'ignore' => [
                '/(?:INSERT|UPDATE|DELETE) INTO "pulse_/',
                '/(?:INSERT|UPDATE|DELETE) INTO "audit_logs"/', // Ignorar overhead de auditoría
            ],
        ],

        Recorders\Servers::class => [
            'server_name' => env('PULSE_SERVER_NAME', gethostname()),
            'directories' => explode(',', env('PULSE_SERVER_DIRECTORIES', '/')),
        ],

        Recorders\UserRequests::class => [
            'enabled' => true,
            'sample_rate' => 1,
            'ignore' => [
                '#^/pulse$#',
                '#^/horizon$#',
                '#^/telescope$#',
            ],
        ],

        Recorders\UserJobs::class => [
            'enabled' => true,
            'sample_rate' => 1,
            'ignore' => [
                // '/^App\\Jobs\\IgnoredJob$/',
            ],
        ],
    ],
];
