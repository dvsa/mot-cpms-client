<?php

namespace CpmsClient\Controller\Plugin;

use Psr\Container\ContainerInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * Class GetRestClient
 * @method AbstractActionController getController()
 *
 * @package CpmsClient\Controller\Plugin
 */
class GetRestClient extends AbstractPlugin
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Work around to get the API domain based on naming convention
     *
     * @return mixed|string
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke()
    {
        return $this->container->get('cpms\service\api');
    }
}
