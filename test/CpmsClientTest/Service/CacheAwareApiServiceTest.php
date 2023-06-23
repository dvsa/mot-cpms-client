<?php
namespace ApplicationTest\Service;

use CpmsClient\Service\ApiService;
use CpmsClient\Service\CacheAwareApiService;
use CpmsClientTest\Bootstrap;
use Laminas\Http\Response;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

/**
 * Class ClientAwareApiServiceTest
 *
 * @package ApplicationTest\Service
 */
class CacheAwareApiServiceTest extends AbstractHttpControllerTestCase
{
    /** @var CacheAwareApiService */
    protected $service;

    /** @var  \Laminas\ServiceManager\ServiceManager */
    protected $serviceManager;

    public function setUp(): void
    {
        $this->setApplicationConfig(
            include __DIR__ . '/../../../' . 'config/application.config.php'
        );

        $this->serviceManager = Bootstrap::getInstance()->getServiceManager();
        $this->setApplicationConfig($this->serviceManager->get('ApplicationConfig'));

        /** @var \CpmsClient\Service\CacheAwareApiService $service */
        $this->service = $this->serviceManager->get('cpms\service\api\cacheAware');
        $this->serviceManager->setAllowOverride(true);
        parent::setUp();
    }

    /**
     * @medium
     */
    public function testApiInstance()
    {
        $this->assertInstanceOf('CpmsClient\Service\CacheAwareApiService', $this->service);
    }

    public function testCachedResult()
    {
        $param = array('limit' => time());
        $this->service->get('/api/transaction', ApiService::SCOPE_CARD, $param);
        $result = $this->service->get('/api/transaction', ApiService::SCOPE_CARD, $param);
        $this->assertTrue(is_array($result));
    }

    public function testStorage()
    {
        $this->assertInstanceOf('Laminas\Cache\Storage\StorageInterface', $this->service->getCacheStorage());
    }

    public function testSaveResultInCache()
    {
        $method   = 'get';
        $arg      = ['access_token', 'CARD'];
        $value    = ['items' => ['first' => 1]];
        $service  = clone $this->service;
        $response = new Response();
        $response->setContent(json_encode($value));
        $service->getServiceProxy()->getClient()->getHttpClient()->getAdapter()->setResponse($response);
        $data = $this->service->__call($method, $arg);

        $this->assertSame($value, $data);

        return $value;
    }

    /**
     * @depends testSaveResultInCache
     */
    public function testCallMagicMethod($value)
    {
        $method = 'get';
        $arg    = ['access_token', 'CARD'];
        $data   = $this->service->__call($method, $arg);

        $this->assertSame($value, $data);
    }
}
