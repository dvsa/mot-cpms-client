<?php
namespace CpmsClient\View\Helper;

use Exception;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Helper\AbstractHelper;
use Laminas\View\HelperPluginManager;

/**
 * Get the API domain name
 *
 * @package CpmsClient\View\Helper
 */
class GetApiDomain extends AbstractHelper
{
    protected ?ServiceLocatorInterface $pluginManager = null;

    public function __invoke()
    {
        return $this->getServiceLocator()->get('cpms\service\domain'); // TODO use ContainerInterface
    }

    /**
     * Set service locator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator): void
    {
        $this->pluginManager = $serviceLocator;
    }

    /**
     * @throws Exception
     */
    public function getServiceLocator(): ServiceLocatorInterface
    {
        if (!isset($this->pluginManager)) {
            throw new Exception('Plugin Manager Not Set');
        }
        return $this->pluginManager;
    }
}
