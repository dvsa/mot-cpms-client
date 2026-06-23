<?php
namespace CpmsClient\Service;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * Class LoggerFactory
 *
 * @package CpmsCommon\Service
 */
class LoggerFactory implements FactoryInterface
{
    const DEFAULT_LOGGER_ALIAS = 'cpms\client\logger'; //default logger if none is set in the app

    /**
     * Creates the logger
     *
     * @param ContainerInterface $container
     *
     * @param $requestedName
     * @param array|null $options
     * @return LoggerInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): LoggerInterface
    {
        $config = $container->get('config');

        $loggerConfig = $config['cpms_client']['logger'] ?? [];

        $filename = rtrim($loggerConfig['location'] ?? 'data/logs', '/') . '/'
            . trim($loggerConfig['filename'] ?? 'cpms-client.log', '/');

        $channel = $loggerConfig['channel'] ?? 'cpms-client';

        $logger = new Logger($channel);
        $logger->pushHandler(new StreamHandler($filename));

        return $logger;
    }

    /**
     * Get log filename from config
     *
     * @param array $config
     *
     * @return string
     */
    public function getLogFilename(array $config)
    {
        if (isset($config['logPath']) and is_file($config['logPath'])) {
            $filename = $config['logPath'];
        } else {
            $filename = rtrim($config['cpms_client']['logger']['location'], '/') . '/' . trim($config['cpms_client']['logger']['filename'], '/');
        }

        return $filename;
    }
}
