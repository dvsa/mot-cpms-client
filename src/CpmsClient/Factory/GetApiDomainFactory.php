<?php

declare(strict_types=1);

namespace CpmsClient\Factory;

use CpmsClient\Controller\Plugin\GetApiDomain;
use Psr\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * @psalm-api
 */
class GetApiDomainFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  array<array-key, mixed>|null $options
     * @return GetApiDomain
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     */
    #[\Override]
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): GetApiDomain
    {
        return new GetApiDomain($container);
    }
}
