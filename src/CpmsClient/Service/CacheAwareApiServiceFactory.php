<?php
namespace CpmsClient\Service;
use Interop\Container\ContainerInterface;
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
