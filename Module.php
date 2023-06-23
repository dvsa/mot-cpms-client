<?php

namespace CpmsClient;

use Laminas\Mvc\ModuleRouteListener;

/**
 * Class Module
 *
 * @package CpmsClient
 */
class Module
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Laminas\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    /**
     * Sets up a generic error handler for the API
     *
     * @param  $event
     *
     * @return void
     */
    public function onBootstrap(\Laminas\Mvc\MvcEvent $event)
    {
        $application         = $event->getApplication();
        $events              = $application->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($events);
    }
}
