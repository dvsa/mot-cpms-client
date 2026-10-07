<?php

namespace CpmsClient\Factory;

use CpmsClient\Controller\Plugin\GetRestClient;
use Psr\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Class GetRestClientFactory
 *
 * @package CpmsClient\Factory
 * @psalm-api
 */
class GetRestClientFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array<array-key, mixed> $options
     * @return GetRestClient
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     */
    #[\Override]
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): GetRestClient
    {
        return new GetRestClient($container);
    }
}
