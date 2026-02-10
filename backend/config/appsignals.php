<?php

return [
    'rate_limits' => [
        'ingest_per_minute' => env('APPSIGNALS_INGEST_RATE_LIMIT_PER_MINUTE', 600),
    ],
    'symbolication' => [
        'enabled' => env('APPSIGNALS_SYMBOLICATION_ENABLED', false),
        'command' => env('APPSIGNALS_SYMBOLICATION_COMMAND', ''),
        'batch_size' => env('APPSIGNALS_SYMBOLICATION_BATCH', 50),
    ],
    'demo_seed' => env('APPSIGNALS_DEMO_SEED', in_array(env('APP_ENV'), ['local', 'testing'], true)),
];
