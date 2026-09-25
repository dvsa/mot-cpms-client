<?php

declare(strict_types=1);

namespace CpmsClient\Client;

use DvsaLogger\Logger\MotLogger;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Laminas\Http\Client;
use Laminas\Http\Client as HttpClient;
use Laminas\Http\Request;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class RestClientFactory
 *
 * @package CpmsClient\Client
 */
class RestClientFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ContainerInterface $container
     *
     * @param $requestedName
     * @param array|null $options
     * @return HttpRestJsonClient
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): HttpRestJsonClient
    {
        $domain                = $container->get('cpms\service\domain');
        $config                = $container->get('config');
        $adapter               = $config['cpms_api']['rest_client']['adapter'];
        $restOptions           = $config['cpms_api']['rest_client']['options'];
        $restOptions['domain'] = $domain;

        $logger = $container->get(MotLogger::class);

        $options                 = new ClientOptions($restOptions);
        $clientOption['timeout'] = $options->getTimeout();
        $httpClient              = new HttpClient(null, $clientOption);
        $request                 = new Request();
        $httpRestJsonClient      = new HttpRestJsonClient($httpClient, $logger, $request);

        $httpClient->setEncType(Client::ENC_FORMDATA);
        $httpClient->setAdapter($adapter);
        $httpRestJsonClient->setOptions($options);

        return $httpRestJsonClient;
    }
}
