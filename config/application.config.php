<?php

return [
    'modules' => [
        'CpmsClient',
        'DvsaLogger',
        'Laminas\Filter',
        'Laminas\Cache',
        'Laminas\Router',
        'Laminas\Validator',
        'Laminas\Cache\Storage\Adapter\Memory',
        'Laminas\Cache\Storage\Adapter\Filesystem',
        'Laminas\Cache\Storage\Adapter\Apcu',
    ],
    'module_listener_options' => [
        'module_paths' => [
            './module',
            './vendor',
        ],
        'config_glob_paths' => [
            __DIR__ . '/autoload/{,*.}{global,local}.php',
        ],
    ],
];
