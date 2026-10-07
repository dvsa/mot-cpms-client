<?php

namespace CpmsClient\Controller\Plugin;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class SendResponse
 * @method AbstractRestfulController getController()
 *
 * @package     CpmsCommon\Controller\Plugin
 * @author      Pele Odiase <pele.odiase@valtech.co.uk>
 * @since       22 June 2014
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
    public function __invoke()
    {
        return $this->container->get('cpms\service\domain');
    }
}
