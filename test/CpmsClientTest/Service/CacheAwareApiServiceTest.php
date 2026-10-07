<?php

namespace ApplicationTest\Service;

use CpmsClient\Service\ApiService;
use CpmsClient\Service\CacheAwareApiService;
use CpmsClientTest\Bootstrap;
use Laminas\Cache\Exception\ExceptionInterface;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\Http\Client\Adapter\Test as TestAdapter;
use Laminas\Http\Response;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class ClientAwareApiServiceTest
 *
 * @package ApplicationTest\Service
 */
class CacheAwareApiServiceTest extends AbstractHttpControllerTestCase
{
    protected CacheAwareApiService $service;

    protected ServiceManager $serviceManager;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[\Override]
    public function setUp(): void
    {
        /** @var array<string, mixed> $applicationConfig */
        $applicationConfig = include __DIR__ . '/../../../' . 'config/application.config.php';
        $this->setApplicationConfig(
            $applicationConfig
        );

        $this->serviceManager = Bootstrap::getInstance()->getServiceManager();
        /** @var array<string, mixed> $applicationConfig */
        $applicationConfig = $this->serviceManager->get('ApplicationConfig');
        $this->setApplicationConfig($applicationConfig);

        /** @var CacheAwareApiService $service */
        $service = $this->serviceManager->get('cpms\service\api\cacheAware');
        $this->service = $service;
        $this->serviceManager->setAllowOverride(true);
        parent::setUp();
    }

    private function setTestResponse(CacheAwareApiService $service, Response $response): void
    {
        $adapter = $service->getServiceProxy()->getClient()->getHttpClient()->getAdapter();

        if (!$adapter instanceof TestAdapter) {
            throw new \RuntimeException('Expected Laminas test adapter in test environment.');
        }

        $adapter->setResponse($response);
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
        $this->service->get('/api/transaction', ApiService::SCOPE_CARD, $param);
        $result = $this->service->get('/api/transaction', ApiService::SCOPE_CARD, $param);
        $this->assertTrue(is_array($result));
    }

    public function testStorage(): void
    {
        $this->assertInstanceOf(StorageInterface::class, $this->service->getCacheStorage());
    }

    /**
     * @throws ExceptionInterface
     *
     * @return array{items: array{first: int}}
     */
    public function testSaveResultInCache(): array
    {
        $method   = 'get';
        $arg      = ['access_token', 'CARD'];
        $value    = ['items' => ['first' => 1]];
        $service  = clone $this->service;
        $response = new Response();
        $response->setContent(json_encode($value));
        $this->setTestResponse($service, $response);
        $data = $this->service->__call($method, $arg);

        $this->assertSame($value, $data);

        return $value;
    }

    /**
     * @depends testSaveResultInCache
     * @throws ExceptionInterface
     *
     * @param array{items: array{first: int}} $value
     */
    public function testCallMagicMethod(array $value): void
    {
        $method = 'get';
        $arg    = ['access_token', 'CARD'];
        $data   = $this->service->__call($method, $arg);

        $this->assertSame($value, $data);
    }
}
