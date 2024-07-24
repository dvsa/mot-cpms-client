<?php

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
 */
class CacheAwareApiServiceFactory implements FactoryInterface
{
    /**
     * Create Cache Aware API Service
     *
     * @param ContainerInterface $container
     *
     * @param string $requestedName
     * @param array|null $options
     * @return CacheAwareApiService
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \Exception
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var ApiService $service */
        $service = $container->get('cpms\service\api');

        $wrapper = new CacheAwareApiService($service);
        $wrapper->setCacheStorage($service->getCacheStorage());
        $wrapper->setLogger($service->getLogger());

        return $wrapper;
    }
}
