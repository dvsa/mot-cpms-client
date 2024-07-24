<?php

namespace CpmsClient\Service;

use CpmsClient\Authenticate\IdentityProviderInterface;
use CpmsClient\Client\HttpRestJsonClient;
use CpmsClient\Client\NotificationsClient;
use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\Log\LoggerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\NotFoundExceptionInterface;

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
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return ApiService
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     *
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
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

        /** @var HttpRestJsonClient $httpRestJsonClient */
        $httpRestJsonClient = $container->get($restClient);
        /** @var AbstractAdapter $cache */
        $cache              = $container->get($config['cpms_api']['cache_storage']);
        $cacheNameSpace     = $cache->getOptions()->getNamespace();

        if (!empty($identityAlias) && $container->has($identityAlias)) {
            /** @var IdentityProviderInterface $identity */
            $identity = $container->get($identityAlias);
            if ($identity instanceof IdentityProviderInterface) {
                $httpRestJsonClient->getOptions()->setUserId($identity->getUserId());
                $httpRestJsonClient->getOptions()->setClientId($identity->getClientId());
                $httpRestJsonClient->getOptions()->setClientSecret($identity->getClientSecret());
                /** @var ?string $customerReference */
                $customerReference = $identity->getCustomerReference();
                $httpRestJsonClient->getOptions()->setCustomerReference($customerReference);
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

        /** @var NotificationsClient $notificationsClient */
        $notificationsClient = $container->get($notificationsClientName);

        /** @var ApiService $service */
        $service = new $serviceClass();
        $cache->getOptions()->setNamespace($cacheNameSpace);
        $service->setLogger($logger);
        $service->setClient($httpRestJsonClient);
        $service->setOptions($httpRestJsonClient->getOptions());
        $service->setCacheStorage($cache);
        $service->setEnableCache($enableCache);
        $service->setNotificationsClient($notificationsClient);

        return $service;
    }
}
