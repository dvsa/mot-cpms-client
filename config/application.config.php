<?php
return array(
    'modules' => array(
        'Laminas\Filter',
        'Laminas\Log',
        'Laminas\Cache',
        'Laminas\Router',
        'Laminas\Validator',
        'CpmsClient',
        'Laminas\Cache\Storage\Adapter\Memory',
        'Laminas\Cache\Storage\Adapter\Filesystem',
        'Laminas\Cache\Storage\Adapter\Apcu',
    ),
    'module_listener_options' => array(
        'module_paths' => array(
            './module',
            './vendor',
        ),
        'config_glob_paths' => array(
            'config/autoload/{,*.}{global,local}.php',
        ),
    )
);
