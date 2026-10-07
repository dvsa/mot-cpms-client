<?php

namespace CpmsClient\Client;

use DVSA\CPMS\Queues\QueueAdapters\Interfaces\Queues;
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
     * @param array<array-key, mixed>|null $options
     * @return NotificationsClient the client to use for notifications from CPMS
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     *
     * @psalm-api
     */
    #[\Override]
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): NotificationsClient
    {
        /**
         * @var array{
         *     cpms_api: array{
         *         notifications_client: array{
         *             adapter: class-string,
         *             options: array<string, mixed>
         *         }
         *     }
         * } $config
         */
        $config = $container->get('config');

        $logger = $container->get(MotLogger::class);
        if (!$logger instanceof MotLogger) {
            throw new \UnexpectedValueException('The logger service must be a MotLogger.');
        }

        $queueOptions = $config['cpms_api']['notifications_client']['options'];

        $adapterName = $config['cpms_api']['notifications_client']['adapter'];
        $adapter = new $adapterName($queueOptions);
        if (!$adapter instanceof Queues) {
            throw new \UnexpectedValueException('The notifications adapter must implement the Queues interface.');
        }

        return new NotificationsClient($adapter, $logger);
    }
}
