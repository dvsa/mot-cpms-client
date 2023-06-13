<?php

use CpmsClientTest\MockLogger;
use CpmsClientTest\MockUser;

return array(
    'application_env'   => 'testing',
    'display_exception' => false,
    'router'            => array(
        'routes' => array(
            'cpms-test' => array(
                'type'    => 'literal',
                'options' => array(
                    'route'    => '/test-index',
                    'defaults' => array(
                        'controller' => 'CpmsClientTest\Sample',
                        'action'     => 'index'
                    )
                ),
            ),
        ),
    ),
    'view_manager'      => array(
        'not_found_template'  => 'error/404',
        'exception_template'  => 'error/index',
        'template_map'        => array(
            'layout/layout' => __DIR__ . '/view/layout/layout.phtml',
            'error/404'     => __DIR__ . '/view/error/404.phtml',
            'error/index'   => __DIR__ . '/view/error/index.phtml',
            'sample/index'  => __DIR__ . '/view/cpms-common/index/index.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/view',
        ),
    ),
    'controllers'       => array(
        'invokables' => array(
            'CpmsClientTest\Sample' => 'CpmsClientTest\SampleController',
        ),
    ),
    'cpms_api'          => array(
        'identity_provider' => 'mock_user',
        'home_domain'       => 'http://payment-app.psqa-ap01.ps.npm',
        'service_class'     => 'CpmsClientTest\MockApiService',
        'rest_client'       => array(
            'options' => array(
                'version' => 2,
                'domain'  => 'http://payment-service.psqa-ap01.ps.npm',
            ),
            'adapter' => 'Laminas\Http\Client\Adapter\Test',
        )
    ),
    'service_manager'   => array(
        'shared'    => array(
            'cpms\service\api'    => false,
            'cpms\client\rest'    => false,
            'cpms\service\domain' => false,
        ),
        'factories' => array(
            'mock_user' => function () {
                $user = new MockUser();
                $user->setClientId('MOT');
                $user->setClientSecret('9014932246b862088130fab632c929c2e11245d4');
                $user->setUserId('89045');
                $user->setCustomerReference('KWIKFIT');
                $user->setCostCentre('12345,89767');

                return $user;
            },
            'cpms\client\logger' => function () {
                return new MockLogger();
            },
        ),
    ),
    'caches'            => array(
        'filesystem' => array(
            'adapter' => 'filesystem',
             'options' => array(
                    'cache_dir' => 'data/cache/cpms',
                ),
            'plugins' => null,
        ),
        'array' => array(
            'adapter' => 'memory',
        ),
        'apc' => array(
            'adapter' => 'apcu',
            'plugins' => null,
        ),
    ),
    'logger'            => array(
        'location' => 'data/logs/',
    ),
);
