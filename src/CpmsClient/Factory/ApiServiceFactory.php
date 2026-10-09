<?php

declare(strict_types=1);

namespace CpmsClient\Factory;

use CpmsClient\Authenticate\IdentityProviderInterface;
use CpmsClient\Client\ClientOptions;
use CpmsClient\Client\HttpRestJsonClient;
use CpmsClient\Client\NotificationsClient;
use CpmsClient\Service\ApiService;
use DvsaLogger\Logger\MotLogger;
use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @psalm-api
 */
class ApiServiceFactory implements FactoryInterface
{
    /**
     * Create API Service
     *
     * @param ContainerInterface $container
     *
     * @param $requestedName
     * @param array<array-key, mixed>|null $options
     * @return ApiService
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[\Override]
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ApiService
    {
        /** @var array{
         *     cpms_api: array{
         *         rest_client: array{alias: string},
         *         enable_cache: bool,
         *         service_class: class-string<ApiService>,
         *         cache_storage: string,
         *         identity_provider: string,
         *         notifications_client?: array{alias?: string}
         *     }
         * } $config
         */
        $config        = $container->get('config');
        $restClient    = $config['cpms_api']['rest_client']['alias'];
        $enableCache   = $config['cpms_api']['enable_cache'];
        $serviceClass  = $config['cpms_api']['service_class'];
        $identityAlias = $config['cpms_api']['identity_provider'];

        /** @var MotLogger $logger */
        $logger = $container->get(MotLogger::class);

        /** @var HttpRestJsonClient $httpRestJsonClient */
        $httpRestJsonClient = $container->get($restClient);
        /** @var AbstractAdapter $cache */
        $cache              = $container->get($config['cpms_api']['cache_storage']);
        $cacheNameSpace     = $cache->getOptions()->getNamespace();

        $clientOptions = $httpRestJsonClient->getOptions();
        if (!$clientOptions instanceof ClientOptions) {
            throw new \UnexpectedValueException('The REST client has no ClientOptions configured.');
        }

        if (!empty($identityAlias) && $container->has($identityAlias)) {
            $identity = $container->get($identityAlias);
            if ($identity instanceof IdentityProviderInterface) {
                $clientOptions->setUserId($identity->getUserId());
                $clientOptions->setClientId($identity->getClientId());
                $clientOptions->setClientSecret($identity->getClientSecret());
                $customerReference = $identity->getCustomerReference();
                if (is_string($customerReference)) {
                    $clientOptions->setCustomerReference($customerReference);
                }
                $cacheNameSpace .= $identity->getClientId();
            }

            if (is_object($identity) && method_exists($identity, 'getVersion')) {
                $version = $identity->getVersion();
                if (is_int($version)) {
                    $clientOptions->setVersion($version);
                }
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

        $service = new $serviceClass();
        $cache->getOptions()->setNamespace($cacheNameSpace);
        $service->setLogger($logger);
        $service->setClient($httpRestJsonClient);
        $service->setOptions($clientOptions);
        $service->setCacheStorage($cache);
        $service->setEnableCache($enableCache);
        $service->setNotificationsClient($notificationsClient);

        return $service;
    }
}
