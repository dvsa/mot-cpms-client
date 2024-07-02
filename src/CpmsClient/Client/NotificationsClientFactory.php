<?php

namespace CpmsClient\Client;

use CpmsClient\Service\LoggerFactory;
use DVSA\CPMS\Queues\QueueAdapters\Interfaces\Queues;
use Laminas\Log\LoggerInterface;
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
     *
     * Required suppression due to un-typed parameter in parent class
     * @psalm-suppress MissingParamType
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): NotificationsClient
    {
        // shorthand
        /** @var array $config */
        $config = $container->get('config');

        // we need a logger
        //
        // best not assume that we have one, just in case
        $loggerAlias = null;
        if (isset($config['cpms_api'], $config['cpms_api']['logger_alias'])) {
            $loggerAlias  = $config['cpms_api']['logger_alias'];
        }
        if ($loggerAlias === '' || $loggerAlias === '0' || $loggerAlias === null || !$container->has($loggerAlias)) {
            $loggerAlias = LoggerFactory::DEFAULT_LOGGER_ALIAS;
        }

        /** @var LoggerInterface $logger */
        $logger = $container->get($loggerAlias);

        // what's the config for our queue adapter?
        $queueOptions = $config['cpms_api']['notifications_client']['options'];

        // which queue adapter are we using?
        $adapterName = $config['cpms_api']['notifications_client']['adapter'];
        /** @var Queues $adapter */
        $adapter = new $adapterName($queueOptions);

        // now, we can build ourselves the client
        return new NotificationsClient($adapter, $logger);
    }
}
