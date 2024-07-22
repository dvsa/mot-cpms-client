<?php

namespace CpmsClient\View\Helper;

use Exception;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Helper\AbstractHelper;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Get the API domain name
 *
 * @package CpmsClient\View\Helper
 */
class GetApiDomain extends AbstractHelper
{
    /** @var ?ServiceLocatorInterface */
    protected $pluginManager = null;

    /**
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function __invoke()
    {
        return $this->getServiceLocator()->get('cpms\service\domain');
    }

    /**
     * Set service locator
     *
     * @return void
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->pluginManager = $serviceLocator;
    }

    /**
     * @return ServiceLocatorInterface
     * @throws Exception
     */
    public function getServiceLocator()
    {
        if (!isset($this->pluginManager)) {
            throw new Exception('Plugin Manager Not Set');
        }
        return $this->pluginManager;
    }
}
