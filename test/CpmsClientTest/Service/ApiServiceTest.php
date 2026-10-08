<?php

namespace CpmsClientTest\Service;

use CpmsClient\Client\HttpRestJsonClient;
use CpmsClient\Client\NotificationsClient;
use CpmsClient\Data\AccessToken;
use CpmsClient\Exceptions\CpmsNotificationAcknowledgementFailed;
use CpmsClient\Service\ApiService;
use CpmsClientTest\Bootstrap;
use CpmsClientTest\MockApiService;
use CpmsClientTest\MockUser;
use CpmsClientTest\SampleController;
use DateTime;
use DVSA\CPMS\Notifications\Ids\ValueBuilders\GenerateNotificationId;
use DVSA\CPMS\Notifications\Messages\Values\PaymentNotificationV1;
use Laminas\Cache\Exception\ExceptionInterface;
use Laminas\Http\Client\Adapter\Test as TestAdapter;
use Laminas\Filter\Word\UnderscoreToCamelCase;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\ControllerManager;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ApiServiceTest extends AbstractHttpControllerTestCase
{
    protected MockApiService $service;
    protected SampleController $controller;
    protected ServiceManager $serviceManager;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[\Override]
    public function setUp(): void
    {
        $this->controller = new SampleController();
        /** @var array<string, mixed> $applicationConfig */
        $applicationConfig = include __DIR__ . '/../../../' . 'config/application.config.php';
        $this->setApplicationConfig(
            $applicationConfig
        );

        $this->serviceManager = Bootstrap::getInstance()->getServiceManager();
        /** @var array<string, mixed> $applicationConfig */
        $applicationConfig = $this->serviceManager->get('ApplicationConfig');
        $this->setApplicationConfig($applicationConfig);

        /** @var MockApiService $service */
        $service = $this->serviceManager->get('cpms\service\api');
        $this->service = $service;
        $this->serviceManager->setAllowOverride(true);
        parent::setUp();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function setTestResponse(Response $response): void
    {
        /** @var HttpRestJsonClient $client */
        $client = $this->serviceManager->get('cpms\client\rest');
        $adapter = $client->getHttpClient()->getAdapter();

        if (!$adapter instanceof TestAdapter) {
            throw new \RuntimeException('Expected Laminas test adapter in test environment.');
        }

        $adapter->setResponse($response);
        $client->getHttpClient()->getResponse()->setStatusCode(200);
        $this->service->setClient($client);
    }


    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testControllerPlugin(): void
    {
        /** @var ControllerManager $loader */
        $loader = $this->getApplicationServiceLocator()->get(ControllerManager::class);
        /** @var SampleController $controller */
        $controller = $loader->get('CpmsClientTest\Sample');
        /** @phpstan-ignore method.notFound */
        $plugin = $controller->getCpmsRestClient();
        $this->assertInstanceOf(ApiService::class, $plugin);
    }

    /**
     * @medium
     * @throws ExceptionInterface
     */
    public function testTokenGenerationNoCache(): void
    {
        $this->service->setEnableCache(false);
        $token = $this->service->getTokenForScope(ApiService::SCOPE_CARD);
        $this->assertInstanceOf(AccessToken::class, $token);

        $this->assertSame('CARD', $token->getScope());
        $this->assertSame('Bearer', $token->getTokenType());
    }

    /**
     * @medium
     * @throws ExceptionInterface
     */
    public function testTokenGenerationCached(): void
    {
        $this->service->setEnableCache(true);
        $token = $this->service->getTokenForScope(ApiService::SCOPE_CARD);
        $this->assertInstanceOf(AccessToken::class, $token);

        $invalidEndPoint = $this->service->getEndpoint('invalid');
        $this->assertSame('invalid', $invalidEndPoint);
    }

    /**
     * @medium
     * @throws ContainerExceptionInterface
     * @throws ExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testProcessRequestGet(): void
    {
        $response = new Response();
        $response->setContent('{"token":"test"}');

        $this->service->setExpiresIn(360);
        $this->service->getTokenForScope(ApiService::SCOPE_QUERY_TXN);
        $this->service->setExpiresIn(1);

        $this->setTestResponse($response);

        $return = $this->service->get('transaction', ApiService::SCOPE_QUERY_TXN, array('time' => time()));
        $this->assertNotEmpty($return);
        $this->service->setExpiresIn(1);
    }

    /**
     * @medium
     * @throws ContainerExceptionInterface
     * @throws ExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testProcessRequestGetWithSalesRef(): void
    {
        $response = new Response();
        $response->setContent('{"token":"test"}');

        $salesRef = 'salesRef';

        $this->service->setExpiresIn(360);
        $this->service->getTokenForScope(ApiService::SCOPE_QUERY_TXN, $salesRef);
        $this->service->setExpiresIn(1);

        $this->setTestResponse($response);
        /** @var MockUser $user */
        $user = $this->serviceManager->get('mock_user');

        $data   = [
            'cost_centre'  => $user->getCostCentre(),
            'payment_data' => [
                [
                    'sales_reference' => $salesRef
                ]
            ]
        ];
        $return = $this->service->get(
            'transaction',
            ApiService::SCOPE_QUERY_TXN,
            $data
        );
        $this->assertNotEmpty($return);
        $this->service->setExpiresIn(1);
    }

    /**
     * @medium
     * @throws ContainerExceptionInterface
     * @throws ExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testProcessRequestGetWithPaymentDataNoSalesRef(): void
    {
        ob_start();
        $response = new Response();
        $response->setContent('{"token":"test"}');

        $this->service->setExpiresIn(360);
        $this->service->setExpiresIn(1);

        $this->setTestResponse($response);

        $return = $this->service->get('transaction', ApiService::SCOPE_QUERY_TXN, ['payment_data' => []]);
        $this->assertNotEmpty($return);
        $this->service->setExpiresIn(1);
        ob_get_clean();
    }

    /**
     * @medium
     * @throws ContainerExceptionInterface
     * @throws ExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testProcessRequestGetRetry(): void
    {
        ob_start();
        $response = new Response();
        $response->setContent('{"token":"test"}');

        $this->service->setExpiresIn(360);
        $this->service->getTokenForScope(ApiService::SCOPE_QUERY_TXN);
        $this->service->setExpiresIn(1);

        $this->setTestResponse($response);
        $this->service->setForceRetry();

        $return = $this->service->get('transaction', ApiService::SCOPE_QUERY_TXN, array('time' => time()));
        $this->assertNotEmpty($return);
        $this->service->setExpiresIn(1);
        ob_get_clean();
    }

    /**
     * @medium
     * @throws ExceptionInterface
     */
    public function testProcessRequestPut(): void
    {
        $return = $this->service->put('transaction', ApiService::SCOPE_QUERY_TXN, array());
        $this->assertNotEmpty($return);
    }

    /**
     * @medium
     * @throws ExceptionInterface
     */
    public function testProcessRequestDelete(): void
    {
        $return = $this->service->delete('transaction', ApiService::SCOPE_QUERY_TXN);
        $this->assertNotEmpty($return);
    }

    /**
     * @medium
     * @throws ExceptionInterface
     */
    public function testInvalidProcessRequest(): void
    {
        $return = $this->service->post('transaction', 'wrong-data', array());

        $this->assertNotEmpty($return);
        /** @var array<string, mixed> $return */
        $this->assertArrayHasKey('code', $return);
        $this->assertArrayHasKey('message', $return);
    }

    public function testAccessTokenData(): void
    {
        $filter = new UnderscoreToCamelCase();
        $data   = array(
            'issued_at'    => time(),
            'access_token' => 'test',
            'expires_in'   => 360,
            'scope'        => 'CARD',
            'token_type'   => 'Bearer'
        );

        $token  = new AccessToken($data);
        $header = $token->getAuthorisationHeader();

        foreach ($data as $key => $value) {
            /** @var string $filteredKey */
            $filteredKey = $filter->filter($key);
            $method      = 'get' . $filteredKey;
            $testValue = $token->$method();
            $this->assertSame($value, $testValue);
        }

        $this->assertNotEmpty($header);
        $this->assertFalse($token->isExpired());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @return void
     */
    public function testLoggerAlias(): void
    {
        /** @var array{cpms_api: array<string, mixed>} $config */
        $config                             = $this->serviceManager->get('config');
        $config['cpms_api']['logger_alias'] = 'logger';
        $this->serviceManager->setService('config', $config);

        /** @var ApiService $apiService */
        $apiService = $this->serviceManager->get('cpms\service\api');
        $this->assertInstanceOf(ApiService::class, $apiService);
    }

    /**
     * @return NotificationsClient
     */
    protected function provideNotificationsClient(): NotificationsClient
    {
        $notificationsClient = $this->service->getNotificationsClient();

        if (!$notificationsClient instanceof NotificationsClient) {
            throw new \RuntimeException('Expected notifications client to be configured in tests.');
        }

        return $notificationsClient;
    }

    /**
     * @covers ::acknowledgeNotification
     * @return void
     * @throws ContainerExceptionInterface
     * @throws CpmsNotificationAcknowledgementFailed
     * @throws NotFoundExceptionInterface
     */
    public function testCanAcknowledgeANotification(): void
    {
        // ----------------------------------------------------------------
        // setup your test
        //
        // there's a lot going on here :)

        $response = new Response();
        $response->setContent('{"code":"000"}');

        $this->setTestResponse($response);

        // we need to put a message onto this queue and read it off again
        // so that we have the metadata required for acknowledgement
        $notificationsClient = $this->provideNotificationsClient();
        $queuesClient = $notificationsClient->getQueuesClient();

        $expectedNotification = new PaymentNotificationV1(
            "unit-test",
            GenerateNotificationId::now(),
            new DateTime("2015-01-01 00:30:00 +0000"),
            "CPMS",
            "unit-test",
            "test",
            "unit-test",
            new DateTime("2015-01-01 00:00:00 +0000"),
            "CPMS-123456-67890",
            3.14
        );

        /** @psalm-suppress InvalidArgument */
        /** @psalm-suppress InvalidCast */
        /** @phpstan-ignore-next-line argument.type */
        $queuesClient->writeMessageToQueue("notifications", $expectedNotification);

        $actualNotifications = $this->service->getNotifications();
        $this->assertCount(1, $actualNotifications);

        $this->assertGreaterThan(0, $notificationsClient->getQueuesClient()->getNumberOfMessagesInQueue("notifications"));

        // ----------------------------------------------------------------
        // perform the change

        $this->service->acknowledgeNotification($actualNotifications[0]['metadata'], $actualNotifications[0]['message']);

        // ----------------------------------------------------------------
        // test the results

        $this->assertEquals(0, $notificationsClient->getQueuesClient()->getNumberOfMessagesInQueue("notifications"));
    }

    /**
     * @covers ::acknowledgeNotification
     * @return void
     * @throws ContainerExceptionInterface
     * @throws CpmsNotificationAcknowledgementFailed
     * @throws ExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testThrowsExceptionIfAcknowledgementFailsWithNoCode(): void
    {
        $this->expectException(CpmsNotificationAcknowledgementFailed::class);
        $response = new Response();
        $response->setContent('{"message":"success"}');

        $this->setTestResponse($response);

        // we need to put a message onto this queue and read it off again
        // so that we have the metadata required for acknowledgement
        $notificationsClient = $this->provideNotificationsClient();
        $queuesClient = $notificationsClient->getQueuesClient();

        $expectedNotification = new PaymentNotificationV1(
            "unit-test",
            GenerateNotificationId::now(),
            new DateTime("2015-01-01 00:30:00 +0000"),
            "CPMS",
            "unit-test",
            "test",
            "unit-test",
            new DateTime("2015-01-01 00:00:00 +0000"),
            "CPMS-123456-67890",
            3.14
        );

        /** @psalm-suppress InvalidArgument */
        /** @psalm-suppress InvalidCast */
        /** @phpstan-ignore-next-line argument.type */
        $queuesClient->writeMessageToQueue("notifications", $expectedNotification);

        $actualNotifications = $this->service->getNotifications();
        $this->assertCount(1, $actualNotifications);

        $this->service->acknowledgeNotification($actualNotifications[0]['metadata'], $actualNotifications[0]['message']);
    }

    /**
     * @covers ::acknowledgeNotification
     * @dataProvider provideInvalidResponseCode
     * @return void
     * @throws ContainerExceptionInterface
     * @throws CpmsNotificationAcknowledgementFailed
     * @throws ExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testThrowsExceptionIfAcknowledgementFailsWithWrongCode(): void
    {
        $this->expectException(CpmsNotificationAcknowledgementFailed::class);
        $response = new Response();
        $response->setContent('{"code":"999"}');

        $this->setTestResponse($response);

        // we need to put a message onto this queue and read it off again
        // so that we have the metadata required for acknowledgement
        $notificationsClient = $this->provideNotificationsClient();
        $queuesClient = $notificationsClient->getQueuesClient();

        $expectedNotification = new PaymentNotificationV1(
            "unit-test",
            GenerateNotificationId::now(),
            new DateTime("2015-01-01 00:30:00 +0000"),
            "CPMS",
            "unit-test",
            "test",
            "unit-test",
            new DateTime("2015-01-01 00:00:00 +0000"),
            "CPMS-123456-67890",
            3.14
        );

        /** @psalm-suppress InvalidArgument */
        /** @psalm-suppress InvalidCast */
        /** @phpstan-ignore-next-line argument.type */
        $queuesClient->writeMessageToQueue("notifications", $expectedNotification);

        $actualNotifications = $notificationsClient->getNotifications();
        $this->assertCount(1, $actualNotifications);

        $this->service->acknowledgeNotification($actualNotifications[0]['metadata'], $actualNotifications[0]['message']);
    }

    /**
     * @return array<int, array{0: array{code: mixed}}>
     */
    public function provideInvalidResponseCode(): array
    {
        // our dataset to test with
        /** @var array<int, array{0: array{code: mixed}}> $retval */
        static $retval = [];

        // PHPUnit 4.0 appears to call data providers multiple times?
        if (count($retval) > 0) {
            return $retval;
        }

        // rather than hardcode a small list here, let's programatically
        // build a larger set
        for ($a = 48; $a < 58; $a++) {
            for ($b = 48; $b < 58; $b++) {
                for ($c = 49; $c < 58; $c++) {
                    $retval[] = [ ['code' => chr($a) . chr($b) . chr($c) ] ];
                }
            }
        }

        // just for good measure, let's throw in some other things as well
        //
        // these are all things that should never happen, but if they do,
        // we do not want the code crashing with an avoidable error
        $retval[] = [ [ 'code' => true ] ];
        $retval[] = [ [ 'code' => false ] ];
        $retval[] = [ [ 'code' => null ] ];
        $retval[] = [ [ 'code' => [] ] ];
        $retval[] = [ [ 'code' => 0.0 ] ];
        $retval[] = [ [ 'code' => 0 ] ];
        $retval[] = [ [ 'code' => '0' ] ];

        // all done
        return $retval;
    }
}
