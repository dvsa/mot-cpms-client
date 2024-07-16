<?php

namespace CpmsClient\Service;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Rest API service
 * Class ApiService
 *
 * @package CpmsClient\Service
 */
class CacheAwareApiServiceFactory implements FactoryInterface
{
    /**
     * Create Cache Aware API Service
     *
     * @param ContainerInterface $container
     *
     * @param $requestedName
     * @param array|null $options
     * @return CacheAwareApiService
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Exception
     *
     * Required suppression due to un-typed parameter in parent class
     * @psalm-suppress MissingParamType
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): CacheAwareApiService
    {
        /** @var ApiService $service */
        $service = $container->get('cpms\service\api');

        return new CacheAwareApiService($service, $service->getLogger(), $service->getCacheStorage());
    }
}
