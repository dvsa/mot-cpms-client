<?php

namespace CpmsClientTest\Client;

use CpmsClient\Client\NotificationsClient;
use CpmsClient\Client\NotificationsClientFactory;
use CpmsClientTest\Bootstrap;
use DVSA\CPMS\Notifications\Messages\Maps\MapNotificationTypes;
use DVSA\CPMS\Queues\QueueAdapters\InMemory\InMemoryQueues;
use PHPUnit\Framework\TestCase;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceManager;

/**
 * @coversDefaultClass \CpmsClient\Client\NotificationsClientFactory
 */
class NotificationsClientFactoryTest extends TestCase
{
    /**
     * ZF2's ServiceManager
     *
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * the config from ZF2's ServiceManager
     *
     * @var array
     */
    protected $smConfig;

    /**
 * automatically called by PHPUnit before every test
 *
 * it provides a working Zend ServiceManager. we'll use this to make sure
 * that our factory is compatible with ZF2
 *
 *
 */
    public function setUp(): void
    {
        // our Zend ServiceManager
        $this->serviceManager = Bootstrap::getInstance()->getServiceManager();

        // so that we can inject test-specific config
        $this->serviceManager->setAllowOverride(true);

        // ZF2's ServiceManager does *not* get created from scratch at the
        // start of each test (grrrr)
        //
        // we need to preserve its original config before each test, and
        // we need to restore that config after each test
        //
        // if we do not do this, the legacy unit tests all break (grrrr)
        $this->smConfig = $this->serviceManager->get('config');
    }

    /**
     * automatically called by PHPUnit after every test
     *
     * @return void
     */
    public function tearDown(): void
    {
        // restore ServiceManager's original config, in case our test
        // has gone and modified it
        //
        // if we do not do this, the legacy unit tests all break (grrrr)
        $this->serviceManager->setService('config', $this->smConfig);
    }

    /**
     * @coversNothing
     */
    public function testCanInstantiate()
    {
        // ----------------------------------------------------------------
        // setup your test

        // ----------------------------------------------------------------
        // perform the change

        $unit = new NotificationsClientFactory;

        // ----------------------------------------------------------------
        // test the results

        $this->assertInstanceOf(NotificationsClientFactory::class, $unit);
    }

    /**
     * @coversNothing
     */
    public function testIsServiceManagerFactory()
    {
        // ----------------------------------------------------------------
        // setup your test

        // ----------------------------------------------------------------
        // perform the change

        $unit = new NotificationsClientFactory;

        // ----------------------------------------------------------------
        // test the results

        $this->assertInstanceOf(FactoryInterface::class, $unit);
    }

    public function testCanCreateNotificationsClient()
    {
        // ----------------------------------------------------------------
        // setup your test
        //
        // we need to inject the config that our factories will ultimately
        // consume

        $config = $this->smConfig;
        $config['cpms_api']['notifications_client'] = [
            'adapter' => InMemoryQueues::class,
            'options' => [
                'queues' => [
                    'notifications' => [
                        'Middleware' => [
                            'MultipartMessage' => [
                                "mapper" => MapNotificationTypes::class,
                            ],
                        ],
                    ],
                ]
            ]
        ];
        $this->serviceManager->setService('config', $config);

        // ----------------------------------------------------------------
        // perform the change

        $client = $this->serviceManager->get('cpms\client\notifications');

        // ----------------------------------------------------------------
        // test the results

        $this->assertInstanceOf(NotificationsClient::class, $client);
    }

    public function testUsesTheDefaultLoggerIfOneIsNotConfigured()
    {
        // ----------------------------------------------------------------
        // setup your test
        //
        // this config does *not* specify any logger at all in the 'cpms_api'
        // section

        $config = $this->smConfig;
        $config['cpms_api']['notifications_client'] = [
            'adapter' => InMemoryQueues::class,
            'options' => [
                'queues' => [
                    'notifications' => [
                        'Middleware' => [
                            'MultipartMessage' => [
                                "mapper" => MapNotificationTypes::class,
                            ],
                        ],
                    ],
                ]
            ]
        ];
        if (isset($config['cpms_api']['logger_alias'])) {
            unset($config['cpms_api']['logger_alias']);
        }
        $this->serviceManager->setService('config', $config);

        // ----------------------------------------------------------------
        // perform the change

        $client = $this->serviceManager->get('cpms\client\notifications');

        // ----------------------------------------------------------------
        // test the results

        $this->assertInstanceOf(NotificationsClient::class, $client);
    }

}