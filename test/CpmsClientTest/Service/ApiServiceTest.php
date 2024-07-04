<?php

namespace ApplicationTest\Service;

use CpmsClient\Client\HttpRestJsonClient;
use CpmsClient\Client\NotificationsClient;
use CpmsClient\Data\AccessToken;
use CpmsClient\Exceptions\CpmsNotificationAcknowledgementFailed;
use CpmsClient\Service\ApiService;
use CpmsClientTest\Bootstrap;
use CpmsClientTest\MockApiService;
use CpmsClientTest\MockUser;
use CpmsClientTest\SampleController;
use CpmsClientTest\TestUtils;
use DateTime;
use DVSA\CPMS\Notifications\Ids\ValueBuilders\GenerateNotificationId;
use DVSA\CPMS\Notifications\Messages\Maps\MapNotificationTypes;
use DVSA\CPMS\Notifications\Messages\Values\PaymentNotificationV1;
use Laminas\Cache\Exception\ExceptionInterface;
use Laminas\Filter\Word\UnderscoreToCamelCase;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\ControllerManager;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class ApiDomainTest
 *
 * @package ApplicationTest\Service
 * @coversDefaultClass CpmsClient\Service\ApiService
 */
class ApiServiceTest extends AbstractHttpControllerTestCase
{
    protected MockApiService $service;
    protected SampleController $controller;
    protected ServiceManager $serviceManager;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function setUp(): void
    {
        $this->controller = new SampleController();
        $this->setApplicationConfig(
            include __DIR__ . '/../../../' . 'config/application.config.php'
        );

        $this->serviceManager = Bootstrap::getInstance()->getServiceManager();

        /** @var array $applicationConfig */
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
    public function testControllerPlugin(): void
    {
        /** @var ControllerManager $loader */
        $loader = $this->getApplicationServiceLocator()->get('ControllerManager');
        /** @var SampleController $controller */
        $controller = $loader->get('CpmsClientTest\Sample');
        /**
         * Magic method created through config, not found in linting
         * @psalm-suppress UndefinedMagicMethod
         * @phpstan-ignore method.notFound
         */
        $plugin = $controller->getCpmsRestClient();
        $this->assertInstanceOf(ApiService::class, $plugin);
    }

    /**
     * @medium
     */
    public function testTokenGenerationNoCache(): void
    {
        $this->service->setEnableCache(false);
        /** @var AccessToken $token */
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
     */
    public function testProcessRequestGet(): void
    {
        $response = new Response();
        $response->setContent('{"token":"test"}');

        $this->service->setExpiresIn(360);
        $this->service->getTokenForScope(ApiService::SCOPE_QUERY_TXN);
        $this->service->setExpiresIn(1);

        /** @var HttpRestJsonClient $client */
        $client = $this->serviceManager->get('cpms\client\rest');
        /**
         * @psalm-suppress UndefinedInterfaceMethod
         * @phpstan-ignore method.notFound
         */
        $client->getHttpClient()->getAdapter()->setResponse($response);
        $client->getHttpClient()->getResponse()->setStatusCode(200);
        $this->service->setClient($client);

        $return = $this->service->get('transaction', ApiService::SCOPE_QUERY_TXN, array('time' => time()));
        $this->assertNotEmpty($return);
        $this->service->setExpiresIn(1);
    }

    /**
     * @medium
     */
    public function testProcessRequestGetWithSalesRef(): void
    {
        $response = new Response();
        $response->setContent('{"token":"test"}');

        $salesRef = 'salesRef';

        $this->service->setExpiresIn(360);
        $this->service->getTokenForScope(ApiService::SCOPE_QUERY_TXN, $salesRef);
        $this->service->setExpiresIn(1);

        /** @var HttpRestJsonClient $client */
        $client = $this->serviceManager->get('cpms\client\rest');
        /**
         * @psalm-suppress UndefinedInterfaceMethod
         * @phpstan-ignore method.notFound
         */
        $client->getHttpClient()->getAdapter()->setResponse($response);
        $client->getHttpClient()->getResponse()->setStatusCode(200);
        $this->service->setClient($client);
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
     */
    public function testProcessRequestGetWithPaymentDataNoSalesRef(): void
    {
        ob_start();
        $response = new Response();
        $response->setContent('{"token":"test"}');

        $salesRef = 'salesRef';

        $this->service->setExpiresIn(360);
        $token = $this->service->getTokenForScope(ApiService::SCOPE_QUERY_TXN, $salesRef);
        $this->service->setExpiresIn(1);

        /** @var HttpRestJsonClient $client */
        $client = $this->serviceManager->get('cpms\client\rest');
        /**
         * @psalm-suppress UndefinedInterfaceMethod
         * @phpstan-ignore method.notFound
         */
        $client->getHttpClient()->getAdapter()->setResponse($response);
        $client->getHttpClient()->getResponse()->setStatusCode(200);
        $this->service->setClient($client);

        $return = $this->service->get('transaction', ApiService::SCOPE_QUERY_TXN, ['payment_data' => []]);
        $this->assertNotEmpty($return);
        $this->service->setExpiresIn(1);
        ob_get_clean();
    }

    /**
     * @medium
     */
    public function testProcessRequestGetRetry(): void
    {
        ob_start();
        $response = new Response();
        $response->setContent('{"token":"test"}');

        $this->service->setExpiresIn(360);
        $this->service->getTokenForScope(ApiService::SCOPE_QUERY_TXN);
        $this->service->setExpiresIn(1);

        /** @var HttpRestJsonClient $client */
        $client = $this->serviceManager->get('cpms\client\rest');
        /**
         * @psalm-suppress UndefinedInterfaceMethod
         * @phpstan-ignore method.notFound
         */
        $client->getHttpClient()->getAdapter()->setResponse($response);
        $client->getHttpClient()->getResponse()->setStatusCode(200);
        $this->service->setClient($client);
        $this->service->setForceRetry();

        $return = $this->service->get('transaction', ApiService::SCOPE_QUERY_TXN, array('time' => time()));
        $this->assertNotEmpty($return);
        $this->service->setExpiresIn(1);
        ob_get_clean();
    }

    /**
     * @medium
     */
    public function testProcessRequestPut(): void
    {
        $return = $this->service->put('transaction', ApiService::SCOPE_QUERY_TXN, array());
        $this->assertNotEmpty($return);
    }

    /**
     * @medium
     */
    public function testProcessRequestDelete(): void
    {
        $return = $this->service->delete('transaction', ApiService::SCOPE_QUERY_TXN);
        $this->assertNotEmpty($return);
    }

    /**
     * @medium
     */
    public function testInvalidProcessRequest(): void
    {
        /** @var array $return */
        $return = $this->service->post('transaction', 'wrong-data', array());

        $this->assertNotEmpty($return);
        $this->assertArrayHasKey('code', $return);
        $this->assertArrayHasKey('message', $return);
    }

    public function testAccessTokenData(): void
    {
        $filter = new UnderscoreToCamelCase();
        /** @var iterable<string, iterable> $data */
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
            $method    = 'get' . $filter->filter($key);
            $testValue = $token->$method();
            $this->assertSame($value, $testValue);
        }

        $this->assertNotEmpty($header);
        $this->assertFalse($token->isExpired());
    }

    public function testLoggerAlias(): void
    {
        /** @var array $config */
        $config = $this->serviceManager->get('config');
        $config['cpms_api']['logger_alias'] = 'logger';
        $this->serviceManager->setService('config', $config);
        /** @var ApiService $apiService */
        $apiService = $this->serviceManager->get('cpms\service\api');
        $this->assertInstanceOf(ApiService::class, $apiService);
    }

    protected function provideNotificationsClient(): NotificationsClient
    {
        return $this->service->getNotificationsClient();
    }

    /**
     * @covers ::acknowledgeNotification
     */
    public function testCanAcknowledgeANotification(): void
    {
        // ----------------------------------------------------------------
        // setup your test
        //
        // there's a lot going on here :)

        $response = new Response();
        $response->setContent('{"code":"000"}');

        /** @var HttpRestJsonClient $client */
        $client = $this->serviceManager->get('cpms\client\rest');
        /**
         * @psalm-suppress UndefinedInterfaceMethod
         * @phpstan-ignore method.notFound
         */
        $client->getHttpClient()->getAdapter()->setResponse($response);
        $client->getHttpClient()->getResponse()->setStatusCode(200);
        $this->service->setClient($client);

        // we need to put a message onto this queue and read it off again
        // so that we have the metadata required for acknowledgement
        $notificationsClient = $this->provideNotificationsClient();
        $queuesClient = $notificationsClient->getQueuesClient();

        /** @var string $expectedNotification */
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
        $mapper = new MapNotificationTypes();
        /** @psalm-suppress InvalidArgument */
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
     */
    public function testThrowsExceptionIfAcknowledgementFailsWithNoCode(): void
    {
        // ----------------------------------------------------------------
        // setup your test

        $this->expectException(CpmsNotificationAcknowledgementFailed::class);
        $response = new Response();
        $response->setContent('{"message":"success"}');

        /** @var HttpRestJsonClient $client */
        $client = $this->serviceManager->get('cpms\client\rest');
        /**
         * @psalm-suppress UndefinedInterfaceMethod
         * @phpstan-ignore method.notFound
         */
        $client->getHttpClient()->getAdapter()->setResponse($response);
        $client->getHttpClient()->getResponse()->setStatusCode(200);
        $this->service->setClient($client);

        // we need to put a message onto this queue and read it off again
        // so that we have the metadata required for acknowledgement
        $notificationsClient = $this->provideNotificationsClient();
        $queuesClient = $notificationsClient->getQueuesClient();

        /** @var string $expectedNotification */
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
        $queuesClient->writeMessageToQueue("notifications", $expectedNotification);
        $actualNotifications = $this->service->getNotifications();
        $this->assertCount(1, $actualNotifications);

        // ----------------------------------------------------------------
        // perform the change

        $this->service->acknowledgeNotification($actualNotifications[0]['metadata'], $actualNotifications[0]['message']);

        // ----------------------------------------------------------------
        // test the results
        //
        // we should never get here
    }

    /**
     * @covers ::acknowledgeNotification
     *
     * @dataProvider provideInvalidResponseCode
     */
    public function testThrowsExceptionIfAcknowledgementFailsWithWrongCode(): void
    {
        // ----------------------------------------------------------------
        // setup your test

        $this->expectException(CpmsNotificationAcknowledgementFailed::class);
        $response = new Response();
        $response->setContent('{"code":"999"}');

        /** @var HttpRestJsonClient $client */
        $client = $this->serviceManager->get('cpms\client\rest');
        /**
         * @psalm-suppress UndefinedInterfaceMethod
         * @phpstan-ignore method.notFound
         */
        $client->getHttpClient()->getAdapter()->setResponse($response);
        $client->getHttpClient()->getResponse()->setStatusCode(200);
        $this->service->setClient($client);

        // we need to put a message onto this queue and read it off again
        // so that we have the metadata required for acknowledgement
        $notificationsClient = $this->provideNotificationsClient();
        $queuesClient = $notificationsClient->getQueuesClient();

        /** @var string $expectedNotification */
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
        $mapper = new MapNotificationTypes();
        /** @psalm-suppress InvalidArgument */
        $queuesClient->writeMessageToQueue('notifications', $expectedNotification);
        $actualNotifications = $notificationsClient->getNotifications();
        $this->assertCount(1, $actualNotifications);

        // ----------------------------------------------------------------
        // perform the change

        $this->service->acknowledgeNotification($actualNotifications[0]['metadata'], $actualNotifications[0]['message']);

        // ----------------------------------------------------------------
        // test the results
        //
        // we should never get here
    }

    public function provideInvalidResponseCode(): array
    {
        // our dataset to test with
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
