<?php

namespace CpmsClientTest\Client;

use CpmsClient\Client\ClientOptions;
use CpmsClient\Client\HttpRestJsonClient;
use CpmsClient\Service\ApiDomainServiceFactory;
use CpmsClient\Service\ApiService;
use CpmsClientTest\Bootstrap;
use Laminas\Cache\Storage\Adapter\Apcu;
use Laminas\Cache\Storage\Adapter\Filesystem;
use Laminas\Cache\Storage\Adapter\Memory;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\Http\Headers;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ParametersInterface;
use PHPUnit\Framework\TestCase;
use Laminas\Http\PhpEnvironment\Request;

/**
 * Class RestClientTest
 *
 * @package CpmsClientTest\Service
 */
class RestClientTest extends TestCase
{
    protected ServiceManager $serviceManager;

    public function setUp(): void
    {
        $this->serviceManager = Bootstrap::getInstance()->getServiceManager();
        $this->serviceManager->setAllowOverride(true);
    }

    public function testClientInstance(): void
    {
        /** @var ApiService $service */
        $service = $this->serviceManager->get('cpms\service\api');
        $service->addHeader('Custom', 'Header');

        $this->assertInstanceOf(ApiService::class, $service);
        $this->assertInstanceOf(HttpRestJsonClient::class, $service->getClient());
        $this->assertInstanceOf(ClientOptions::class, $service->getClient()->getOptions());
        $this->assertInstanceOf(StorageInterface::class, $service->getCacheStorage());
    }

    public function testResetHeaders(): void
    {
        /** @var ApiService $service */
        $service = $this->serviceManager->get('cpms\service\api');

        $headers                  = $service->getClient()->getOptions()->getHeaders();
        $headers['Authorization'] = 'Authorization';
        $service->getClient()->getOptions()->setHeaders($headers);

        /** @var \Laminas\Http\Request $request */
        $request = $service->getClient()->resetHeaders();
        $headers = $request->getHeaders();

        $this->assertInstanceOf(Headers::class, $headers);
        $this->assertCount(0, $headers);
    }

    public function testEmptyDomain(): void
    {
        /** @var array $config */
        $config = $this->serviceManager->get('config');
        $host   = $config['cpms_api']['rest_client']['options']['domain'];

        $config['cpms_api']['rest_client']['options']['domain'] = '';
        $this->serviceManager->setService('config', $config);

        $factory      = new ApiDomainServiceFactory();
        $request      = new Request();
        /** @var ParametersInterface $serverParams */
        $serverParams = $request->getServer();
        $serverParams->offsetSet('HTTP_HOST', $host);
        $request->setServer($serverParams);

        $domain = $factory->determineLocalDomain($request, $config);
        $this->assertSame($host, $domain);
    }

    public function testCacheAdapters(): void
    {
        /** @var \Laminas\Cache\Storage\StorageInterface $cache */
        $cache = $this->serviceManager->get('filesystem');
        $this->assertInstanceOf(Filesystem::class, $cache);

        $cache = $this->serviceManager->get('array');
        $this->assertInstanceOf(Memory::class, $cache);

        if (extension_loaded('apc') and ini_get('apc.enable_cli') === '1') {
            $cache = $this->serviceManager->get('apc');
            $this->assertInstanceOf(Apcu::class, $cache);
        }
    }
}
