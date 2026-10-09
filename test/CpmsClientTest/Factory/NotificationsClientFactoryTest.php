<?php

declare(strict_types=1);

namespace CpmsClientTest\Factory;

use CpmsClient\Client\NotificationsClient;
use CpmsClient\Factory\NotificationsClientFactory;
use CpmsClientTest\Bootstrap;
use DVSA\CPMS\Notifications\Messages\Maps\MapNotificationTypes;
use DVSA\CPMS\Queues\QueueAdapters\InMemory\InMemoryQueues;
use DvsaLogger\Logger\MotLogger;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @coversDefaultClass \CpmsClient\Factory\NotificationsClientFactory
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
    protected ServiceManager $serviceManager;

    /**
     * @var array<string, mixed>
     */
    protected array $smConfig;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[\Override]
    public function setUp(): void
    {
        $this->serviceManager = Bootstrap::getInstance()->getServiceManager();

        $this->serviceManager->setAllowOverride(true);

        /** @var array<string, mixed> $config */
        $config = $this->serviceManager->get('config');
        $this->smConfig = $config;
    }

    #[\Override]
    public function tearDown(): void
    {
        $this->serviceManager->setService('config', $this->smConfig);
    }

    public function testCanInstantiate(): void
    {
        $unit = new NotificationsClientFactory();

        $this->assertInstanceOf(NotificationsClientFactory::class, $unit);
    }

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
        /**
         * @var array{
         *     cpms_api: array{
         *         notifications_client: array<string, mixed>,
         *         logger_alias?: string
         *     }
         * } $config
         */
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
        /**
         * @var array{
         *     cpms_api: array{
         *         notifications_client: array<string, mixed>,
         *         logger_alias?: string
         *     }
         * } $config
         */
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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testIncorrectLoggerThrowsException(): void
    {
        /** @var MotLogger $originalLogger */
        $originalLogger = $this->serviceManager->get(MotLogger::class);

        $this->serviceManager->setService(MotLogger::class, new \stdClass());

        /**
         * @var array{
         *      cpms_api: array{
         *          notifications_client: array{
         *             adapter: class-string<InMemoryQueues>,
         *             options: array<string, mixed>
         *          },
         *          logger_alias?: string
         *      }
         * } $config
         */
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

        $this->expectException(\UnexpectedValueException::class);

        try {
            (new NotificationsClientFactory())(
                $this->serviceManager,
                'cpms\\client\\notifications',
            );
        } finally {
            $this->serviceManager->setService(MotLogger::class, $originalLogger);
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testIncorrectQueueAdapterThrowsException(): void
    {
        /**
         * @var array{
         *     cpms_api: array{
         *         notifications_client: array<string, mixed>,
         *         logger_alias?: string
         *     }
         * } $config
         */
        $config = $this->smConfig;
        $config['cpms_api']['notifications_client'] = [
            'adapter' => \stdClass::class,
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

        $this->expectException(\UnexpectedValueException::class);

        (new NotificationsClientFactory())(
            $this->serviceManager,
            'cpms\\client\\notifications',
        );
    }
}
