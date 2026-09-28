<?php

return [

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'storage/*',   // 👈 AGREGA ESTA LÍNEA
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],   // desarrollo

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];