<?php
namespace ApplicationTest\Service;

use CpmsClient\Data\AccessToken;
use CpmsClient\Exceptions\CpmsNotificationAcknowledgementFailed;
use CpmsClient\Service\ApiService;
use CpmsClient\Service\LoggerFactory;
use CpmsClient\View\Helper\GetApiDomain;
use CpmsClientTest\Bootstrap;
use CpmsClientTest\MockUser;
use CpmsClientTest\SampleController;
use DateTime;
use DVSA\CPMS\Notifications\Ids\ValueBuilders\GenerateNotificationId;
use DVSA\CPMS\Notifications\Messages\Maps\MapNotificationTypes;
use DVSA\CPMS\Notifications\Messages\Values\PaymentNotificationV1;
use Laminas\Filter\Word\UnderscoreToCamelCase;
use Laminas\Http\Response;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Laminas\View\HelperPluginManager;

/**
 * Class ApiDomainTest
 *
 * @package ApplicationTest\Service
 * @coversDefaultClass CpmsClient\Service\ApiService
 */
class ApiServiceTest extends AbstractHttpControllerTestCase
{
    /** @var \CpmsClientTest\MockApiService $service */
    protected $service;
    /** @var  SampleController */
    protected $controller;
    /** @var  \Laminas\ServiceManager\ServiceManager */
    protected $serviceManager;

    public function setUp(): void
    {
        $this->controller = new SampleController();
        $this->setApplicationConfig(
            include __DIR__ . '/../../../' . 'config/application.config.php'
        );

        $this->serviceManager = Bootstrap::getInstance()->getServiceManager();
        $this->setApplicationConfig($this->serviceManager->get('ApplicationConfig'));

        /** @var \CpmsClient\Service\ApiService $service */
        $this->service = $this->serviceManager->get('cpms\service\api');
        $this->serviceManager->setAllowOverride(true);
        parent::setUp();
    }


    public function testControllerPlugin()
    {
        $loader = $this->getApplicationServiceLocator()->get('ControllerManager');
        /** @var SampleController $controller */
        $controller = $loader->get('CpmsClientTest\Sample');
        $plugin     = $controller->getCpmsRestClient();
        $this->assertInstanceOf('CpmsClient\Service\ApiService', $plugin);
    }

    /**
     * @medium
     */
    public function testTokenGenerationNoCache()
    {
        $this->service->setEnableCache(false);
        /** @var \CpmsClient\Data\AccessToken $token */
        $token = $this->service->getTokenForScope(ApiService::SCOPE_CARD);
        $this->assertInstanceOf('CpmsClient\Data\AccessToken', $token);

        $this->assertSame('CARD', $token->getScope());
        $this->assertSame('Bearer', $token->getTokenType());
    }

    /**
     * @medium
     */
    public function testTokenGenerationCached()
    {
        $this->service->setEnableCache(true);
        $token = $this->service->getTokenForScope(ApiService::SCOPE_CARD);
        $this->assertInstanceOf('CpmsClient\Data\AccessToken', $token);

        $invalidEndPoint = $this->service->getEndpoint('invalid');
        $this->assertSame('invalid', $invalidEndPoint);
    }

    /**
     * @medium
     */
    public function testProcessRequestGet()
    {
        $response = new Response();
        $response->setContent('{"token":"test"}');

        $this->service->setExpiresIn(360);
        $this->service->getTokenForScope(ApiService::SCOPE_QUERY_TXN);
        $this->service->setExpiresIn(1);

        /** @var \CpmsClient\Client\HttpRestJsonClient $client */
        $client = $this->serviceManager->get('cpms\client\rest');
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
    public function testProcessRequestGetWithSalesRef()
    {
        $response = new Response();
        $response->setContent('{"token":"test"}');

        $salesRef = 'salesRef';

        $this->service->setExpiresIn(360);
        $this->service->getTokenForScope(ApiService::SCOPE_QUERY_TXN, $salesRef);
        $this->service->setExpiresIn(1);

        /** @var \CpmsClient\Client\HttpRestJsonClient $client */
        $client = $this->serviceManager->get('cpms\client\rest');
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
            'transaction', ApiService::SCOPE_QUERY_TXN, $data
        );
        $this->assertNotEmpty($return);
        $this->service->setExpiresIn(1);
    }

    /**
     * @medium
     */
    public function testProcessRequestGetWithPaymentDataNoSalesRef()
    {
        ob_start();
        $response = new Response();
        $response->setContent('{"token":"test"}');

        $salesRef = 'salesRef';

        $this->service->setExpiresIn(360);
        $token = $this->service->getTokenForScope(ApiService::SCOPE_QUERY_TXN, $salesRef);
        $this->service->setExpiresIn(1);

        /** @var \CpmsClient\Client\HttpRestJsonClient $client */
        $client = $this->serviceManager->get('cpms\client\rest');
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
    public function testProcessRequestGetRetry()
    {
        ob_start();
        $response = new Response();
        $response->setContent('{"token":"test"}');

        $this->service->setExpiresIn(360);
        $this->service->getTokenForScope(ApiService::SCOPE_QUERY_TXN);
        $this->service->setExpiresIn(1);

        /** @var \CpmsClient\Client\HttpRestJsonClient $client */
        $client = $this->serviceManager->get('cpms\client\rest');
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
    public function testProcessRequestPut()
    {
        $return = $this->service->put('transaction', ApiService::SCOPE_QUERY_TXN, array());
        $this->assertNotEmpty($return);
    }

    /**
     * @medium
     */
    public function testProcessRequestDelete()
    {
        $return = $this->service->delete('transaction', ApiService::SCOPE_QUERY_TXN);
        $this->assertNotEmpty($return);
    }

    /**
     * @medium
     */
    public function testInvalidProcessRequest()
    {
        $return = $this->service->post('transaction', 'wrong-data', array());

        $this->assertNotEmpty($return);
        $this->assertArrayHasKey('code', $return);
        $this->assertArrayHasKey('message', $return);
    }

    public function testAccessTokenData()
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
            $method    = 'get' . $filter->filter($key);
            $testValue = $token->$method();
            $this->assertSame($value, $testValue);
        }

        $this->assertNotEmpty($header);
        $this->assertFalse($token->isExpired());
    }

    public function testLoggerAlias()
    {
        $config                             = $this->serviceManager->get('config');
        $config['cpms_api']['logger_alias'] = 'logger';
        $this->serviceManager->setService('config', $config);

        $apiService = $this->serviceManager->get('cpms\service\api');
        $this->assertInstanceOf('CpmsClient\Service\ApiService', $apiService);
    }

    /**
     * @return NotificationsClient
     */
    protected function provideNotificationsClient()
    {
        return $this->service->getNotificationsClient();
    }

    /**
     * @covers ::acknowledgeNotification
     */
    public function testCanAcknowledgeANotification()
    {
        // ----------------------------------------------------------------
        // setup your test
        //
        // there's a lot going on here :)

        $response = new Response();
        $response->setContent('{"code":"000"}');

        /** @var \CpmsClient\Client\HttpRestJsonClient $client */
        $client = $this->serviceManager->get('cpms\client\rest');
        $client->getHttpClient()->getAdapter()->setResponse($response);
        $client->getHttpClient()->getResponse()->setStatusCode(200);
        $this->service->setClient($client);

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
        $mapper = new MapNotificationTypes;
        $queuesClient->writeMessageToQueue("notifications", $expectedNotification);
        $actualNotifications = $this->service->getNotifications();
        $this->assertTrue(is_array($actualNotifications));
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
    public function testThrowsExceptionIfAcknowledgementFailsWithNoCode()
    {
        // ----------------------------------------------------------------
        // setup your test

        $this->expectException(CpmsNotificationAcknowledgementFailed::class);
        $response = new Response();
        $response->setContent('{"message":"success"}');

        /** @var \CpmsClient\Client\HttpRestJsonClient $client */
        $client = $this->serviceManager->get('cpms\client\rest');
        $client->getHttpClient()->getAdapter()->setResponse($response);
        $client->getHttpClient()->getResponse()->setStatusCode(200);
        $this->service->setClient($client);

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
        $mapper = new MapNotificationTypes;
        $queuesClient->writeMessageToQueue("notifications", $expectedNotification);
        $actualNotifications = $this->service->getNotifications();
        $this->assertTrue(is_array($actualNotifications));
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
     * @dataProvider provideInvalidResponseCode
     */
    public function testThrowsExceptionIfAcknowledgementFailsWithWrongCode($response)
    {
        // ----------------------------------------------------------------
        // setup your test

        $this->expectException(CpmsNotificationAcknowledgementFailed::class);
        $response = new Response();
        $response->setContent('{"code":"999"}');

        /** @var \CpmsClient\Client\HttpRestJsonClient $client */
        $client = $this->serviceManager->get('cpms\client\rest');
        $client->getHttpClient()->getAdapter()->setResponse($response);
        $client->getHttpClient()->getResponse()->setStatusCode(200);
        $this->service->setClient($client);

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
        $mapper = new MapNotificationTypes;
        $queuesClient->writeMessageToQueue("notifications", $expectedNotification);
        $actualNotifications = $notificationsClient->getNotifications();
        $this->assertTrue(is_array($actualNotifications));
        $this->assertCount(1, $actualNotifications);

        // ----------------------------------------------------------------
        // perform the change

        $this->service->acknowledgeNotification($actualNotifications[0]['metadata'], $actualNotifications[0]['message']);

        // ----------------------------------------------------------------
        // test the results
        //
        // we should never get here
    }

    public function provideInvalidResponseCode()
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
                    $retval[] = [ ['code' => chr($a).chr($b).chr($c) ] ];
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
