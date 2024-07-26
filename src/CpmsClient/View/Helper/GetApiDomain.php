<?php
namespace CpmsClient\View\Helper;

use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Helper\AbstractHelper;

/**
 * Get the API domain name
 *
 * @package CpmsClient\View\Helper
 */
class GetApiDomain extends AbstractHelper
{
    /** @var \Laminas\View\HelperPluginManager */
    protected $pluginManager;

    public function __invoke()
    {
        return $this->getServiceLocator()->get('cpms\service\domain'); // TODO use ContainerInterface
    }

    /**
     * Set service locator
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->pluginManager = $serviceLocator;
    }

    /**
     * @return ServiceLocatorInterface|\Laminas\View\HelperPluginManager
     */
    public function getServiceLocator()
    {
        return $this->pluginManager;
    }
}
