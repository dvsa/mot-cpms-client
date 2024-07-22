<?php

namespace CpmsClient\Service;

use CpmsClient\Utility\Util;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class ApiDomainServiceFactory
 *
 * @package CpmsClient\Service
 */
class ApiDomainServiceFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param Containerinterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     *
     * Required suppression due to mismatched return type in parent class
     * @psalm-suppress ImplementedReturnTypeMismatch
     * @phpstan-ignore-next-line
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        /** @var array $config */
        $config = $container->get('config');

        if (empty($config['cpms_api']['rest_client']['options']['domain'])) {
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
     * @param mixed $request
     * @param array $config
     *
     * @return mixed
     * @throws Exception
     */
    public function determineLocalDomain($request, $config)
    {
        if ($request instanceof Request) {
            $currentDomain = $request->getServer('HTTP_HOST');
        } else {
            $currentDomain = $config['cpms_api']['home_domain'];
        }

        // This check is to set a default value that prevents str_replace being called with a null value
        // Passing null to the $subject parameter of str_replace is deprecated
        if (!isset($currentDomain)) {
            $currentDomain = '';
        }

        return str_replace('payment-app', 'payment-service', $currentDomain);
    }
}
