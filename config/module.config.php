<?php

use DVSA\CPMS\Queues\QueueAdapters\AmazonSqs\AmazonSqsQueues;
use DVSA\CPMS\Notifications\Messages\Maps\MapNotificationTypes;

return array(
    'service_manager'    => array(
        'abstract_factories' => array(
            'Laminas\Cache\Service\StorageCacheAbstractServiceFactory',
        ),
        'factories'          => array(
            'cpms\service\api'            => 'CpmsClient\Service\ApiServiceFactory',
            'cpms\service\api\cacheAware' => 'CpmsClient\Service\CacheAwareApiServiceFactory',
            'cpms\service\domain'         => 'CpmsClient\Service\ApiDomainServiceFactory',
            'cpms\client\rest'            => 'CpmsClient\Client\RestClientFactory',
            'cpms\client\logger'          => 'CpmsClient\Service\LoggerFactory',
            'cpms\client\notifications'   => 'CpmsClient\Client\NotificationsClientFactory',
        ),
    ),

    'controller_plugins' => array(
        'factories' => array(
            CpmsClient\Controller\Plugin\GetRestClient::class => CpmsClient\Factory\GetRestClientFactory::class
        ),
        'aliases' => array(
            'getCpmsRestClient' => CpmsClient\Controller\Plugin\GetRestClient::class
        )
    ),

    'caches'             => array(
        'filesystem' => array(
            'adapter' => 'filesystem',
                'lifetime' => 300,
                'options'  => array(
                    'cache_dir'       => 'data/cache/cpms',
                    'ttl'             => 300,
                    'namespace'       => 'cpms',
                    'dir_permission'  => 0775,
                    'file_permission' => 0666,
                ),
                'plugins'  => array(
                    'exception_handler' => array(
                        'throw_exceptions' => false
                    ),
                    'serializer' => array(),
            ),
        ),

        'array'      => array(
            'adapter' => 'memory',
                'lifetime' => 0,
                'options'  => array(
                    'ttl'       => 0,
                    'namespace' => 'cpms'
                ),
        ),
        'apc'        => array(
            'adapter' =>  'apcu',
                'options' => array(
                    'ttl'       => 3600,
                    'namespace' => 'cpms'
            ),
            'plugins' => array(
                'exception_handler' => array(
                    'throw_exceptions' => false
                ),
            ),
        )
    ),

    'logger'             => array(
        'location' => '/var/log/dvsa',
        'filename' => date('Y-m-d') . '-cpms-api-client.log'
    ),

    'cpms_api'           => array(
        'logger_alias'      => '',//'cpms\client\logger',
        'enable_cache'      => true,
        'service_class'     => 'CpmsClient\Service\ApiService',
        'home_domain'       => '', //Used when running in console mode
        'cache_storage'     => (extension_loaded('apc') and php_sapi_name() != 'cli') ? 'apc' : 'array',
        'identity_provider' => '',
        'rest_client'       => array(
            'alias'   => 'cpms\client\rest',
            'options' => array(
                'domain'             => '',
                'version'            => 1,
                'client_id'          => '',
                'client_secret'      => '',
                'user_id'            => '',
                'customer_reference' => '',
                'grant_type'         => 'client_credentials',
                'timeout'            => 15,
                'headers'            => array(
                    'Accept' => 'application/json',
                ),
                'end_points'         => array(
                    'access_token' => '/api/token',
                    'refund'       => '/api/payment/refund',
                    'transaction'  => '/api/transactions'
                )
            ),
            'adapter' => 'Laminas\Http\Client\Adapter\Curl',
        ),
        'notifications_client' => array (
            // Should implement DVSA\CPMS\Queues\Interfaces\Queues
            'adapter' => AmazonSqsQueues::class,
            'options' => array (
                // replace this with the AWS region that your environment is built in
                'region' => 'us-west-2',
                'queues' => array (
                    'notifications' => array (
                        // replace this with the correct notifications queue
                        // for your scheme
                        'QueueUrl' => 'https://sqs.us-west-2.amazonaws.com/600499240829/SH_Test01',
                        'Middleware' => [
                            'MultipartMessage' => [
                                "mapper" => MapNotificationTypes::class,
                            ],
                        ],
                    ),
                ),
            ),
        ),
    ),
);
