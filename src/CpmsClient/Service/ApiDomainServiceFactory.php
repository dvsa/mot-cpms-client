<?php

namespace CpmsClient\Service;

use CpmsClient\Utility\Util;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Laminas\Http\Request;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class ApiDomainServiceFactory
 *
 * @package CpmsClient\Service
 *
 * @psalm-api
 */
class ApiDomainServiceFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ContainerInterface $container
     *
     * @param $requestedName
     * @param array<array-key, mixed>|null $options
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @psalm-suppress LessSpecificImplementedReturnType
     */
    #[\Override]
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): mixed
    {
        /**
         * @var array{
         *     cpms_api: array{
         *         rest_client: array{options: array{domain?: string}},
         *         home_domain?: string
         *     }
         * } $config
         */
        $config = $container->get('config');

        if (empty($config['cpms_api']['rest_client']['options']['domain'])) {
            /** @var \Laminas\Http\PhpEnvironment\Request $request */
            $request   = $container->get('request');
            $apiDomain = $this->determineLocalDomain($request, $config);
        } else {
            $apiDomain = $config['cpms_api']['rest_client']['options']['domain'];
        }

        return Util::appendQueryString($apiDomain);
    }

    /**
     * Determine the CPMS API domain if not set in the config
     *
     * @param \Laminas\Http\PhpEnvironment\Request $request
     * @param array<string, mixed> $config
     *
     * @return string
     */
    public function determineLocalDomain(\Laminas\Http\PhpEnvironment\Request $request, array $config): string
    {
        $currentDomain = $request->getServer('HTTP_HOST');
        if (!is_string($currentDomain) || $currentDomain === '') {
            $cpmsApi = $config['cpms_api'] ?? [];
            $homeDomain = is_array($cpmsApi) ? ($cpmsApi['home_domain'] ?? '') : '';
            $currentDomain = is_string($homeDomain) ? $homeDomain : '';
        }

        return str_replace('payment-app', 'payment-service', $currentDomain);
    }
}
