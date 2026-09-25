<?php

return [
    'mot_logger' => [
        'channel' => 'cpms-api-client',
        'environment_levels' => [
            'dev' => 'debug',
            'int' => 'info',
            'prv' => 'warning',
            'pre-prod' => 'error',
            'prod' => 'critical',
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
];

