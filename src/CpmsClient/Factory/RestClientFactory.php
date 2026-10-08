<?php

declare(strict_types=1);

namespace CpmsClient\Factory;

use CpmsClient\Client\ClientOptions;
use CpmsClient\Client\HttpRestJsonClient;
use DvsaLogger\Logger\MotLogger;
use Laminas\Http\Client;
use Laminas\Http\Request;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class RestClientFactory
 *
 * @package CpmsClient\Client
 * @psalm-api
 */
class RestClientFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ContainerInterface $container
     *
     * @param $requestedName
     * @param array<array-key, mixed>|null $options
     * @return HttpRestJsonClient
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[\Override]
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): HttpRestJsonClient
    {
        /** @var string $domain */
        $domain = $container->get('cpms\service\domain');

        /**
         * @var array{
         *     cpms_api: array{
         *         rest_client: array{
         *             adapter: string,
         *             options: array<string, mixed>
         *         }
         *     }
         * } $config
         */
        $config                = $container->get('config');
        $adapter               = $config['cpms_api']['rest_client']['adapter'];
        $restOptions           = $config['cpms_api']['rest_client']['options'];
        $restOptions['domain'] = $domain;

        /** @var MotLogger $logger */
        $logger = $container->get(MotLogger::class);

        $options                 = new ClientOptions($restOptions);
        $clientOption['timeout'] = $options->getTimeout();
        $httpClient              = new Client(null, $clientOption);
        $request                 = new Request();
        $httpRestJsonClient      = new HttpRestJsonClient($httpClient, $logger, $request);

        $httpClient->setEncType(Client::ENC_FORMDATA);
        $httpClient->setAdapter($adapter);
        $httpRestJsonClient->setOptions($options);

        return $httpRestJsonClient;
    }
}
