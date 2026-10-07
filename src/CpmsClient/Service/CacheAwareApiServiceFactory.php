<?php

declare(strict_types=1);

namespace CpmsClient\Service;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Rest API service
 * Class ApiService
 *
 * @package CpmsClient\Service
 * @psalm-api
 */
class CacheAwareApiServiceFactory implements FactoryInterface
{
    /**
     * Create Cache Aware API Service
     *
     * @param ContainerInterface $container
     *
     * @param $requestedName
     * @param array<array-key, mixed>|null $options
     * @return CacheAwareApiService
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[\Override]
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): CacheAwareApiService
    {
        /** @var ApiService $service */
        $service = $container->get('cpms\service\api');

        $wrapper = new CacheAwareApiService($service);
        $wrapper->setCacheStorage($service->getCacheStorage());

        return $wrapper;
    }
}
