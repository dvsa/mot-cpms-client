<?php

use CpmsClientTest\MockLogger;
use CpmsClientTest\MockUser;
use DvsaLogger\Logger\MotLogger;

return [
    'application_env'   => 'testing',
    'display_exception' => false,
    'router'            => [
        'routes' => [
            'cpms-test' => [
                'type'    => 'literal',
                'options' => [
                    'route'    => '/test-index',
                    'defaults' => [
                        'controller' => 'CpmsClientTest\Sample',
                        'action'     => 'index'
                    ]
                ],
            ],
        ],
    ],
    'mot_logger' => [
        'channel' => 'cpms-api-client-test',
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
                'enabled' => false,
            ]
        ],
    ],

    'view_manager'      => [
        'not_found_template'  => 'error/404',
        'exception_template'  => 'error/index',
        'template_map'        => [
            'layout/layout' => __DIR__ . '/view/layout/layout.phtml',
            'error/404'     => __DIR__ . '/view/error/404.phtml',
            'error/index'   => __DIR__ . '/view/error/index.phtml',
            'sample/index'  => __DIR__ . '/view/cpms-common/index/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/view',
        ],
    ],
    'controllers'       => [
        'invokables' => [
            'CpmsClientTest\Sample' => 'CpmsClientTest\SampleController',
        ],
    ],
    'cpms_api'          => [
        'identity_provider' => 'mock_user',
        'home_domain'       => 'http://payment-app.psqa-ap01.ps.npm',
        'service_class'     => 'CpmsClientTest\MockApiService',
        'rest_client'       => [
            'options' => [
                'version' => 2,
                'domain'  => 'http://payment-service.psqa-ap01.ps.npm',
            ],
            'adapter' => 'Laminas\Http\Client\Adapter\Test',
        ],
    ],
    'service_manager'   => [
        'shared'    => [
            'cpms\service\api'    => false,
            'cpms\client\rest'    => false,
            'cpms\service\domain' => false,
        ],
        'factories' => [
            'mock_user' => function () {
                $user = new MockUser();
                $user->setClientId('MOT');
                $user->setClientSecret('9014932246b862088130fab632c929c2e11245d4');
                $user->setUserId('89045');
                $user->setCustomerReference('KWIKFIT');
                $user->setCostCentre('12345,89767');

                return $user;
            },
        ],
    ],
    'caches'            => [
        'filesystem' => [
            'adapter' => 'filesystem',
             'options' => [
                    'cache_dir' => 'data/cache/cpms',
                ],
            'plugins' => null,
        ],
        'array' => [
            'adapter' => 'memory',
        ],
        'apc' => [
            'adapter' => 'apcu',
            'plugins' => null,
        ],
    ],
    'logger'            => [
        'location' => 'data/logs/',
    ],
];
