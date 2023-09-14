<?php
namespace CpmsClient\Service;

use CpmsClient\Utility\Util;
use Interop\Container\ContainerInterface;
use Laminas\Http\Request;
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
     * @param ContainerInterface $container
     *
     * @param $requestedName
     * @param array|null $options
     * @return mixed
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {

        $config = $container->get('config');

        if (empty($config['cpms_api']['rest_client']['options']['domain'])) {
            /** @var \Laminas\Http\PhpEnvironment\Request $request */
            $request   = $container->get('request');
            $apiDomain = $this->determineLocalDomain($request, $config);
        } else {
            $apiDomain = $config['cpms_api']['rest_client']['options']['domain'];
        }

        $apiDomain = Util::appendQueryString($apiDomain);

        return $apiDomain;
    }

    /**
     * Determine the CPMS API domain if not set in the config
     *
     * @param $request
     * @param $config
     *
     * @return mixed
     */
    public function determineLocalDomain($request, $config)
    {
        /** @var \Laminas\Http\PhpEnvironment\Request $request */
        if ($request instanceof Request) {
            $currentDomain = $request->getServer('HTTP_HOST');
        } else {
            $currentDomain = $config['cpms_api']['home_domain'];
        }

        return str_replace('payment-app', 'payment-service', $currentDomain ?? '');
    }
}
