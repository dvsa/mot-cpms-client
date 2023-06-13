<?php
namespace CpmsClient\Controller\Plugin;

use Interop\Container\ContainerInterface;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

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
    private $container;

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
        $apiDomain = $this->container->get('cpms\service\domain');

        return $apiDomain;
    }
}
