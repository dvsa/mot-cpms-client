<?php

namespace CpmsClient\Client;

use CpmsClient\Service\LoggerFactory;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class NotificationsClientFactory implements FactoryInterface
{
    /**
     * create the notifications client
     *
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     * @return NotificationsClient the client to use for notifications from CPMS
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        // shorthand
        $config = $container->get('config');

        // we need a logger
        //
        // best not assume that we have one, just in case
        $loggerAlias = null;
        if (isset($config['cpms_api'], $config['cpms_api']['logger_alias'])) {
            $loggerAlias  = $config['cpms_api']['logger_alias'];
        }
        if (empty($loggerAlias) || !$container->has($loggerAlias)) {
            $loggerAlias = LoggerFactory::DEFAULT_LOGGER_ALIAS;
        }
        $logger = $container->get($loggerAlias);

        // what's the config for our queue adapter?
        $queueOptions = $config['cpms_api']['notifications_client']['options'];

        // which queue adapter are we using?
        $adapterName = $config['cpms_api']['notifications_client']['adapter'];
        $adapter = new $adapterName($queueOptions);

        // now, we can build ourselves the client
        $notificationsClient = new NotificationsClient($adapter, $logger);

        // all done
        return $notificationsClient;
    }
}