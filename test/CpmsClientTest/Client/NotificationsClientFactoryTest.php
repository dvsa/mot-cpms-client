<?php

declare(strict_types=1);

namespace CpmsClientTest\Client;

use CpmsClient\Client\NotificationsClient;
use CpmsClient\Client\NotificationsClientFactory;
use CpmsClientTest\Bootstrap;
use DVSA\CPMS\Notifications\Messages\Maps\MapNotificationTypes;
use DVSA\CPMS\Queues\QueueAdapters\InMemory\InMemoryQueues;
use PHPUnit\Framework\TestCase;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @coversDefaultClass \CpmsClient\Client\NotificationsClientFactory
 *
 * @phpstan-type CpmsApiConfig array{
 *     cpms_api: array{
 *     notifications_client: array{adapter: class-string<InMemoryQueues>, options: array{queues: array{notifications: array{Middleware: array{MultipartMessage: array{"mapper": class-string<MapNotificationTypes>}}}}}},
 *     enable_cache: bool,
 *     service_class: class-string<NotificationsClient>,
 *     cache_storage: string,
 *     identity_provider: string,
 *     logger_alias?: string
 *     }
 * }
 */
class NotificationsClientFactoryTest extends TestCase
{
    /**
     * ZF2's ServiceManager
     *
     * @var ServiceManager
     */
    protected ServiceManager $serviceManager;

    /**
     * the config from ZF2's ServiceManager
     *
     * @var array<string, mixed>
     */
    protected array $smConfig;

    /**
     * automatically called by PHPUnit before every test
     *
     * it provides a working Zend ServiceManager. we'll use this to make sure
     * that our factory is compatible with ZF2
     *
     */
    #[\Override]
    public function setUp(): void
    {
        $this->serviceManager = Bootstrap::getInstance()->getServiceManager();

        $this->serviceManager->setAllowOverride(true);

        // ZF2's ServiceManager does *not* get created from scratch at the
        // start of each test (grrrr)
        //
        // we need to preserve its original config before each test, and
        // we need to restore that config after each test
        //
        // if we do not do this, the legacy unit tests all break (grrrr)
        /** @var array<string, mixed> $config */
        $config = $this->serviceManager->get('config');
        $this->smConfig = $config;
    }

    /**
     * automatically called by PHPUnit after every test
     *
     * @return void
     */
    #[\Override]
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
    public function testCanInstantiate(): void
    {
        $unit = new NotificationsClientFactory();

        $this->assertInstanceOf(NotificationsClientFactory::class, $unit);
    }

    /**
     * @coversNothing
     */
    public function testIsServiceManagerFactory(): void
    {
        $unit = new NotificationsClientFactory();

        $this->assertInstanceOf(FactoryInterface::class, $unit);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testCanCreateNotificationsClient(): void
    {
        /** @var array{cpms_api: array{notifications_client: array<string, mixed>, logger_alias?: string}} $config */
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

        $client = $this->serviceManager->get('cpms\client\notifications');

        $this->assertInstanceOf(NotificationsClient::class, $client);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testUsesTheDefaultLoggerIfOneIsNotConfigured(): void
    {
        /** @var array{cpms_api: array{notifications_client: array<string, mixed>, logger_alias?: string}} $config */
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

        $client = $this->serviceManager->get('cpms\client\notifications');

        $this->assertInstanceOf(NotificationsClient::class, $client);
    }
}
