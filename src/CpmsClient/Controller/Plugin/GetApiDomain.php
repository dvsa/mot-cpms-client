<?php

declare(strict_types=1);

namespace CpmsClient\Controller\Plugin;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @method AbstractRestfulController getController()
 */
class GetApiDomain extends AbstractPlugin
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    /**
     * Work around to get the API domain based on naming convention
     *
     * @return mixed|string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(): mixed
    {
        return $this->container->get('cpms\service\domain');
    }
}
