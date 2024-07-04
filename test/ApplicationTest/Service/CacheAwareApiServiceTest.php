<?php

namespace ApplicationTest\Service;

use CpmsClient\Service\ApiService;
use CpmsClient\Service\CacheAwareApiService;
use CpmsClientTest\Bootstrap;
use Laminas\Cache\Exception\ExceptionInterface;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\Http\Response;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

/**
 * Class ClientAwareApiServiceTest
 *
 * @package ApplicationTest\Service
 */
class CacheAwareApiServiceTest extends AbstractHttpControllerTestCase
{
    protected CacheAwareApiService $service;
    protected ServiceManager $serviceManager;

    public function setUp(): void
    {
        $this->setApplicationConfig(
            include __DIR__ . '/../../../' . 'config/application.config.php'
        );

        $this->serviceManager = Bootstrap::getInstance()->getServiceManager();
        /** @var array $config */
        $config = $this->serviceManager->get('ApplicationConfig');
        $this->setApplicationConfig($config);

        /** @var CacheAwareApiService $service */
        $service = $this->serviceManager->get('cpms\service\api\cacheAware');
        $this->service = $service;
        $this->serviceManager->setAllowOverride(true);
        parent::setUp();
    }

    /**
     * @medium
     */
    public function testApiInstance(): void
    {
        $this->assertInstanceOf(CacheAwareApiService::class, $this->service);
    }

    public function testCachedResult(): void
    {
        $param = array('limit' => time());
        /** @phpstan-ignore method.notFound */
        $this->service->get('/api/transaction', ApiService::SCOPE_CARD, $param);
        /** @phpstan-ignore method.notFound */
        $result = $this->service->get('/api/transaction', ApiService::SCOPE_CARD, $param);
        $this->assertTrue(is_array($result));
    }

    /**
     * @throws \Exception
     */
    public function testStorage(): void
    {
        $this->assertInstanceOf(StorageInterface::class, $this->service->getCacheStorage());
    }

    /**
     * @throws ExceptionInterface
     */
    public function testSaveResultInCache(): array
    {
        $method   = 'get';
        $arg      = ['access_token', 'CARD'];
        $value    = ['items' => ['first' => 1]];
        $service  = clone $this->service;
        $response = new Response();
        $response->setContent(json_encode($value));

        /**
         * @psalm-suppress UndefinedInterfaceMethod
         * @phpstan-ignore method.notFound
         */
        $service->getServiceProxy()->getClient()->getHttpClient()->getAdapter()->setResponse($response);
        $data = $this->service->__call($method, $arg);

        $this->assertSame($value, $data);

        return $value;
    }

    /**
     * @depends testSaveResultInCache
     */
    public function testCallMagicMethod(mixed $value): void
    {
        $method = 'get';
        $arg    = ['access_token', 'CARD'];
        $data   = $this->service->__call($method, $arg);

        $this->assertSame($value, $data);
    }
}
