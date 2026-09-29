<?php

return [
    'mot_logger' => [
        'channel' => 'cpms-api-client',
        'environment_levels' => [
            'dev' => 'debug',
            'int' => 'debug',
            'prv' => 'warning',
            'pre-prod' => 'error',
            'prod' => 'error',
        ],
        'writers' => [
            [
                'type' => 'stream',
                'path' => 'php://stderr',
                'formatter' => 'pipe',
                'level' => 'error',
                'enabled' => true,
            ],
            [
                'type' => 'stream',
                'path' => '/var/log/dvsa/cpms-api-client.log',
                'formatter' => 'json',
                'level' => 'error',
                'enabled' => true,
            ]
        ],
    ],
    'cpms_api' => [
        // CPMS API configuration values are set in the module config.
        // You can override them here if you want to change to custom values.
    ],
];

