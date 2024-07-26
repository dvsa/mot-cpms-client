<?php
namespace CpmsClient\Client;

use CpmsClient\Service\LoggerFactory;
use Interop\Container\ContainerInterface;
use Laminas\Http\Client;
use Laminas\Http\Client as HttpClient;
use Laminas\Http\Request;
use Laminas\ServiceManager\Factory\FactoryInterface;

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
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $domain                = $container->get('cpms\service\domain');
        $config                = $container->get('config');
        $adapter               = $config['cpms_api']['rest_client']['adapter'];
        $restOptions           = $config['cpms_api']['rest_client']['options'];
        $restOptions['domain'] = $domain;
        $loggerAlias           = $config['cpms_api']['logger_alias'];

        if (empty($loggerAlias) || !$container->has($loggerAlias)) {
            $loggerAlias = LoggerFactory::DEFAULT_LOGGER_ALIAS;
        }
        $logger = $container->get($loggerAlias);

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
