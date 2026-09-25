<?php

namespace CpmsClient\Client;

use CpmsClient\Service\LoggerFactory;
use DvsaLogger\Logger\MotLogger;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\NotFoundExceptionInterface;

class NotificationsClientFactory implements FactoryInterface
{
    /**
     * create the notifications client
     *
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     * @return NotificationsClient the client to use for notifications from CPMS
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): NotificationsClient
    {
        // shorthand
        $config = $container->get('config');

        $logger = $container->get(MotLogger::class);

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
