<?php

namespace CpmsClient\Service;

use Psr\Container\ContainerInterface;
use Laminas\Log\Logger;
use Laminas\Log\Writer\Stream;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Class LoggerFactory
 *
 * @package CpmsCommon\Service
 */
class LoggerFactory implements FactoryInterface
{
    public const DEFAULT_LOGGER_ALIAS = 'cpms\client\logger'; //default logger if none is set in the app

    /**L
     * Creates the logger
     *
     * @param ContainerInterface $container
     *
     * @param mixed $requestedName
     * @param array|null $options
     * @return Logger
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     *
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): Logger
    {
        /** @var array $config */
        $config   = $container->get('config');
        $filename = $this->getLogFilename($config);
        $writer   = new Stream($filename);
        $logger   = new Logger();
        $logger->addWriter($writer);

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
            $filename = rtrim($config['logger']['location'], '/') . '/' . trim($config['logger']['filename'], '/');
        }

        return $filename;
    }
}
