<?php

declare(strict_types=1);

namespace CpmsClientTest;

/**
 * Class Module
 *
 * @package ApplicationTest
 */
class Module
{
    /**
     * @return mixed
     */
    public function getConfig(): mixed
    {
        return include __DIR__ . '/../test.global.php';
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getAutoloaderConfig(): array
    {
        return [
            'Laminas\Loader\StandardAutoloader' => [
                'namespaces' => [
                    'Laminas\Console' => realpath('./src/Laminas/Console'),
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ],
            ],
        ];
    }
}
