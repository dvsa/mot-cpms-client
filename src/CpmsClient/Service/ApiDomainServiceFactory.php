<?php
namespace CpmsClient\Service;

use CpmsClient\Utility\Util;
use Exception;
use Psr\Container\ContainerInterface;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\ServiceManager\Factory\FactoryInterface;

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
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     *
     * Required suppression due to un-typed parameter in parent class
     * @psalm-suppress MissingParamType
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): string
    {

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
     * @throws Exception
     */
    public function determineLocalDomain(mixed $request, array $config): mixed
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
