<?php
namespace CpmsClientTest\Client;

use CpmsClient\Service\ApiDomainServiceFactory;
use CpmsClientTest\Bootstrap;
use PHPUnit\Framework\TestCase;
use Laminas\Http\PhpEnvironment\Request;

/**
 * Class RestClientTest
 *
 * @package CpmsClientTest\Service
 */
class RestClientTest extends TestCase
{

    /** @var  \Laminas\ServiceManager\ServiceManager */
    protected $serviceManager;

    public function setUp(): void
    {
        $this->serviceManager = Bootstrap::getInstance()->getServiceManager();
        $this->serviceManager->setAllowOverride(true);
    }

    public function testClientInstance()
    {
        /** @var \CpmsClient\Service\ApiService $service */
        $service = $this->serviceManager->get('cpms\service\api');
        $service->addHeader('Custom', 'Header');

        $this->assertInstanceOf('CpmsClient\Service\ApiService', $service);
        $this->assertInstanceOf('CpmsClient\Client\HttpRestJsonClient', $service->getClient());
        $this->assertInstanceOf('CpmsClient\Client\ClientOptions', $service->getClient()->getOptions());
        $this->assertInstanceOf('Laminas\Cache\Storage\StorageInterface', $service->getCacheStorage());
    }

    public function testResetHeaders()
    {
        $service = $this->serviceManager->get('cpms\service\api');

        $headers                  = $service->getClient()->getOptions()->getHeaders();
        $headers['Authorization'] = 'Authorization';
        $service->getClient()->getOptions()->setHeaders($headers);

        /** @var \Laminas\Http\Request $request */
        $request = $service->getClient()->resetHeaders();

        $this->assertEquals(0, count($request->getHeaders()));
    }

    public function testEmptyDomain()
    {
        $config = $this->serviceManager->get('config');
        $host   = $config['cpms_api']['rest_client']['options']['domain'];

        $config['cpms_api']['rest_client']['options']['domain'] = '';
        $this->serviceManager->setService('config', $config);


        $factory      = new ApiDomainServiceFactory();
        $request      = new Request();
        $serverParams = $request->getServer();
        $serverParams->offsetSet('HTTP_HOST', $host);
        $request->setServer($serverParams);

        $domain = $factory->determineLocalDomain($request, $config);
        $this->assertSame($host, $domain);
    }

    public function testCacheAdapters()
    {
        /** @var \Laminas\Cache\Storage\StorageInterface $cache */
        $cache = $this->serviceManager->get('filesystem');
        $this->assertInstanceOf('Laminas\Cache\Storage\Adapter\Filesystem', $cache);

        $cache = $this->serviceManager->get('array');
        $this->assertInstanceOf('Laminas\Cache\Storage\Adapter\Memory', $cache);

        if (extension_loaded('apc') and ini_get('apc.enable_cli')) {
            $cache = $this->serviceManager->get('apc');
            $this->assertInstanceOf('Laminas\Cache\Storage\Adapter\Apcu', $cache);
        }
    }
}
