<?php

namespace CpmsClient\Factory;

use CpmsClient\Controller\Plugin\GetApiDomain;
use Psr\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;

class GetApiDomainFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return GetApiDomain
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     *
     * Required suppression due to un-typed parameter in parent class
     * @psalm-suppress MissingParamType
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new GetApiDomain($container);
    }
}
