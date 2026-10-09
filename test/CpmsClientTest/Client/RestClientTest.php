<?php

declare(strict_types=1);

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
use Laminas\Stdlib\Parameters;
use PHPUnit\Framework\TestCase;
use Laminas\Http\PhpEnvironment\Request;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @phpstan-type CpmsApiConfig array{
 *     cpms_api: array{
 *         rest_client: array{alias: string, options: array{domain: string}},
 *         enable_cache: bool,
 *         service_class: class-string<ApiService>,
 *         cache_storage: string,
 *         identity_provider: string,
 *         notifications_client?: array{alias?: string}
 *     }
 * }
 */
class RestClientTest extends TestCase
{
    protected ServiceManager $serviceManager;

    #[\Override]
    public function setUp(): void
    {
        $this->serviceManager = Bootstrap::getInstance()->getServiceManager();
        $this->serviceManager->setAllowOverride(true);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testResetHeaders(): void
    {
        /** @var ApiService $service */
        $service = $this->serviceManager->get('cpms\service\api');

        /** @var ClientOptions $clientOptions */
        $clientOptions = $service->getClient()->getOptions();
        $headers = $clientOptions->getHeaders();
        $headers['Authorization'] = 'Authorization';
        $clientOptions->setHeaders($headers);

        /** @var Request $request */
        $request = $service->getClient()->resetHeaders();

        $headers = $request->getHeaders();
        $this->assertInstanceOf(Headers::class, $headers);
        $headersCount = count($headers->toArray());
        $this->assertEquals(0, $headersCount);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testEmptyDomain(): void
    {
        /** @var CpmsApiConfig $config */
        $config = $this->serviceManager->get('config');
        $host = $config['cpms_api']['rest_client']['options']['domain'];

        $config['cpms_api']['rest_client']['options']['domain'] = '';
        $this->serviceManager->setService('config', $config);

        $factory      = new ApiDomainServiceFactory();
        $request      = new Request();
        /** @var Parameters<string, mixed> $serverParams */
        $serverParams = $request->getServer();
        $serverParams->offsetSet('HTTP_HOST', $host);
        $request->setServer($serverParams);

        $domain = $factory->determineLocalDomain($request, $config);
        $this->assertSame($host, $domain);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function testCacheAdapters(): void
    {
        /** @var StorageInterface $cache */
        $cache = $this->serviceManager->get('filesystem');
        $this->assertInstanceOf(Filesystem::class, $cache);

        $cache = $this->serviceManager->get('array');
        $this->assertInstanceOf(Memory::class, $cache);

        if (extension_loaded('apc') and ini_get('apc.enable_cli')) {
            $cache = $this->serviceManager->get('apc');
            $this->assertInstanceOf(Apcu::class, $cache);
        }
    }
}
