<?php

use CpmsClient\Client\NotificationsClientFactory;
use CpmsClient\Client\RestClientFactory;
use CpmsClient\Service\ApiDomainServiceFactory;
use CpmsClient\Service\ApiService;
use CpmsClient\Service\ApiServiceFactory;
use CpmsClient\Service\CacheAwareApiServiceFactory;
use DVSA\CPMS\Queues\QueueAdapters\AmazonSqs\AmazonSqsQueues;
use DVSA\CPMS\Notifications\Messages\Maps\MapNotificationTypes;
use DvsaLogger\Factory\MotLoggerFactory;
use DvsaLogger\Logger\MotLogger;

return [
    'service_manager'    => [
        'abstract_factories' => [
            'Laminas\Cache\Service\StorageCacheAbstractServiceFactory',
        ],
        'factories'          => [
            'cpms\service\api'            => ApiServiceFactory::class,
            'cpms\service\api\cacheAware' => CacheAwareApiServiceFactory::class,
            'cpms\service\domain'         => ApiDomainServiceFactory::class,
            'cpms\client\rest'            => RestClientFactory::class,
            'cpms\client\notifications'   => NotificationsClientFactory::class,
            MotLogger::class              => MotLoggerFactory::class,
        ],
    ],

    'controller_plugins' => [
        'factories' => [
            CpmsClient\Controller\Plugin\GetRestClient::class => CpmsClient\Factory\GetRestClientFactory::class
        ],
        'aliases' => [
            'getCpmsRestClient' => CpmsClient\Controller\Plugin\GetRestClient::class
        ]
    ],

    'caches'             => [
        'filesystem' => [
            'adapter' => 'filesystem',
                'lifetime' => 300,
                'options'  => [
                    'cache_dir'       => 'data/cache/cpms',
                    'ttl'             => 300,
                    'namespace'       => 'cpms',
                    'dir_permission'  => 0775,
                    'file_permission' => 0666,
                ],
                'plugins'  => [
                    'exception_handler' => [
                        'throw_exceptions' => false
                    ],
                    'serializer' => [],
            ],
        ],

        'array'      => [
            'adapter' => 'memory',
                'lifetime' => 0,
                'options'  => [
                    'ttl'       => 0,
                    'namespace' => 'cpms'
                ],
        ],
        'apc'        => [
            'adapter' =>  'apcu',
                'options' => [
                    'ttl'       => 3600,
                    'namespace' => 'cpms'
            ],
            'plugins' => [
                'exception_handler' => [
                    'throw_exceptions' => false
                ],
            ],
        ],
    ],

    'cpms_api'           => [
        'logger_alias'      => MotLogger::class,
        'enable_cache'      => true,
        'service_class'     => ApiService::class,
        'home_domain'       => '', //Used when running in console mode
        'cache_storage'     => (extension_loaded('apc') and php_sapi_name() != 'cli') ? 'apc' : 'array',
        'identity_provider' => '',
        'rest_client'       => [
            'alias'   => 'cpms\client\rest',
            'options' => [
                'domain'             => '',
                'version'            => 1,
                'client_id'          => '',
                'client_secret'      => '',
                'user_id'            => '',
                'customer_reference' => '',
                'grant_type'         => 'client_credentials',
                'timeout'            => 15,
                'headers'            => [
                    'Accept' => 'application/json',
                ],
                'end_points'         => [
                    'access_token' => '/api/token',
                    'refund'       => '/api/payment/refund',
                    'transaction'  => '/api/transactions'
                ]
            ],
            'adapter' => 'Laminas\Http\Client\Adapter\Curl',
        ],
        'notifications_client' => [
            // Should implement DVSA\CPMS\Queues\Interfaces\Queues
            'adapter' => AmazonSqsQueues::class,
            'options' => [
                // replace this with the AWS region that your environment is built in
                'region' => 'us-west-2',
                'queues' => [
                    'notifications' => [
                        // replace this with the correct notifications queue
                        // for your scheme
                        'QueueUrl' => 'https://sqs.us-west-2.amazonaws.com/600499240829/SH_Test01',
                        'Middleware' => [
                            'MultipartMessage' => [
                                "mapper" => MapNotificationTypes::class,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
