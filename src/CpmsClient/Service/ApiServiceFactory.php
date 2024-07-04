<?php

namespace CpmsClient\Service;

use CpmsClient\Authenticate\IdentityProviderInterface;
use CpmsClient\Client\HttpRestJsonClient;
use CpmsClient\Client\NotificationsClient;
use Laminas\Log\LoggerInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Rest API service
 * Class ApiService
 *
 * @package CpmsClient\Service
 */
class ApiServiceFactory implements FactoryInterface
{
    /**
     * Create API Service
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     *
     * Required suppression due to un-typed parameter in parent class
     * @psalm-suppress MissingParamType
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ApiService
    {
        /** @var array $config */
        $config        = $container->get('config');
        $restClient    = $config['cpms_api']['rest_client']['alias'];
        $enableCache   = $config['cpms_api']['enable_cache'];
        $serviceClass  = $config['cpms_api']['service_class'];
        $loggerAlias   = $config['cpms_api']['logger_alias'];
        $identityAlias = $config['cpms_api']['identity_provider'];

        if (empty($loggerAlias) || !$container->has($loggerAlias)) {
            $loggerAlias = LoggerFactory::DEFAULT_LOGGER_ALIAS;
        }
        /** @var LoggerInterface $logger */
        $logger = $container->get($loggerAlias);

        /** @var \Laminas\Cache\Storage\Adapter\AbstractAdapter $cache */
        /** @var HttpRestJsonClient $httpRestJsonClient */
        $httpRestJsonClient = $container->get($restClient);
        $cache              = $container->get($config['cpms_api']['cache_storage']);
        $cacheNameSpace     = $cache->getOptions()->getNamespace();

        if (!empty($identityAlias) && $container->has($identityAlias)) {
            $identity = $container->get($identityAlias);
            if ($identity instanceof IdentityProviderInterface) {
                $httpRestJsonClient->getOptions()->setUserId($identity->getUserId());
                $httpRestJsonClient->getOptions()->setClientId($identity->getClientId());
                $httpRestJsonClient->getOptions()->setClientSecret($identity->getClientSecret());
                $httpRestJsonClient->getOptions()->setCustomerReference($identity->getCustomerReference());
                $cacheNameSpace .= $identity->getClientId();
            }

            if (method_exists($identity, 'getVersion') and $version = $identity->getVersion()) {
                $httpRestJsonClient->getOptions()->setVersion($version);
            }
        }

        // robustness - use a default client with the possibility of
        // overriding if required
        $notificationsClientName = 'cpms\client\notifications';
        if (isset($config['cpms_api']['notifications_client']['alias'])) {
            $notificationsClientName = $config['cpms_api']['notifications_client']['alias'];
        }

        /** @var NotificationsClient */
        $notificationsClient = $container->get($notificationsClientName);

        $cache->getOptions()->setNamespace($cacheNameSpace);

        /** @var ApiService $service */
        $service = new $serviceClass($logger, $httpRestJsonClient, $cache, $enableCache, $notificationsClient);

        return $service;
    }
}
