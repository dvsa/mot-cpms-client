<?php

declare(strict_types=1);

namespace CpmsClientTest\Client;

use CpmsClient\Client\NotificationsClient;
use DateTime;
use DVSA\CPMS\Notifications\Ids\ValueBuilders\GenerateNotificationId;
use DVSA\CPMS\Notifications\Messages\Maps\MapNotificationTypes;
use DVSA\CPMS\Notifications\Messages\Values\PaymentNotificationV1;
use DVSA\CPMS\Queues\QueueAdapters\InMemory\InMemoryQueues;
use DVSA\CPMS\Queues\QueueAdapters\Interfaces\Queues;
use DvsaLogger\Logger\MotLogger;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \CpmsClient\Client\NotificationsClient
 */
class NotificationsClientTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testCanInstantiate(): void
    {
        $unit = $this->provideNotificationsClient();
        $this->assertInstanceOf(NotificationsClient::class, $unit);
    }

    /**
     * @param array<string, mixed> $extraConfig
     */
    protected function provideNotificationsClient(array $extraConfig = []): NotificationsClient
    {
        $queuesConfig = [
            'queues' => [
                'notifications' => [
                    'active' => true,
                    'Middleware' => [
                        'MultipartMessage' => [
                            "mapper" => MapNotificationTypes::class,
                        ]
                    ]
                ]
            ]
        ];

        $queuesConfig = array_merge_recursive($queuesConfig, $extraConfig);
        $queues = new InMemoryQueues($queuesConfig);
        $logger = $this->createMock(MotLogger::class);

        return new NotificationsClient($queues, $logger);
    }

    /**
     * @covers ::getNotifications
     */
    public function testCanGetNotificationsFromQueue(): void
    {
        $unit = $this->provideNotificationsClient();
        $queuesClient = $unit->getQueuesClient();

        $expectedNotification = $this->createTestNotification(GenerateNotificationId::now());
        $this->enqueueNotifications($queuesClient, [$expectedNotification]);

        $actualNotifications = $unit->getNotifications();

        $this->assertCount(1, $actualNotifications);
        $this->assertArrayHasKey(0, $actualNotifications);
        $this->assertArrayHasKey('message', $actualNotifications[0]);
        $this->assertEquals($expectedNotification, $actualNotifications[0]['message']);
    }

    /**
     * @covers ::getNotifications
     */
    public function testReturnsEmptyListWhenNoNotificationsAvailable(): void
    {
        $unit = $this->provideNotificationsClient();
        $actualNotifications = $unit->getNotifications();
        $this->assertCount(0, $actualNotifications);
    }

    /**
     * @covers ::getNotifications
     */
    public function testCanReturnMultipleNotifications(): void
    {
        $extraConfig = $this->maxMessagesConfig(5);
        $unit = $this->provideNotificationsClient($extraConfig);
        $queuesClient = $unit->getQueuesClient();

        $expectedNotifications = $this->createTestNotifications();

        $this->enqueueNotifications($queuesClient, $expectedNotifications);

        $actualNotifications = $unit->getNotifications();

        $this->assertCount(5, $actualNotifications);

        foreach ($actualNotifications as $key => $actualNotification) {
            $this->assertArrayHasKey($key, $expectedNotifications);
            $this->assertEquals($expectedNotifications[$key], $actualNotification['message']);
        }
    }

    /**
     * @covers ::getNotifications
     */
    public function testWillOnlyReturnUpToMaxNumberOfNotifications(): void
    {
        $extraConfig = $this->maxMessagesConfig(2);
        $unit = $this->provideNotificationsClient($extraConfig);
        $queuesClient = $unit->getQueuesClient();

        $expectedNotifications = $this->createTestNotifications();

        $this->enqueueNotifications($queuesClient, $expectedNotifications);

        $actualNotifications = $unit->getNotifications();

        $this->assertCount(2, $actualNotifications);

        foreach ($actualNotifications as $key => $actualNotification) {
            $this->assertArrayHasKey($key, $expectedNotifications);
            $this->assertEquals($expectedNotifications[$key], $actualNotification['message']);
        }
    }

    /**
     * @covers ::getNotifications
     */
    public function testDoesNotWaitIfThereAreLessThanMaxNumberOfNotifications(): void
    {
        $extraConfig = $this->maxMessagesConfig(20);
        $unit = $this->provideNotificationsClient($extraConfig);
        $queuesClient = $unit->getQueuesClient();

        $expectedNotifications = $this->createTestNotifications();

        $this->enqueueNotifications($queuesClient, $expectedNotifications);

        $actualNotifications = $unit->getNotifications();

        $this->assertCount(5, $actualNotifications);

        foreach ($actualNotifications as $key => $actualNotification) {
            $this->assertArrayHasKey($key, $expectedNotifications);
            $this->assertEquals($expectedNotifications[$key], $actualNotification['message']);
        }
    }

    /**
     * @covers ::getQueuesClient
     */
    public function testCanGetTheQueuesClient(): void
    {
        $unit = $this->provideNotificationsClient();
        $actualClient = $unit->getQueuesClient();
        $this->assertInstanceOf(Queues::class, $actualClient);
    }

    private function createTestNotification(string $notificationId): PaymentNotificationV1
    {
        return new PaymentNotificationV1(
            'unit-test',
            $notificationId,
            new DateTime('2015-01-01 00:30:00 +0000'),
            'CPMS',
            'unit-test',
            'test',
            'unit-test',
            new DateTime('2015-01-01 00:00:00 +0000'),
            'CPMS-123456-67890',
            3.14
        );
    }

    /**
     * @return array<int, PaymentNotificationV1>
     */
    private function createTestNotifications(): array
    {
        $notifications = [];
        for ($notificationId = 1; $notificationId <= 5; $notificationId++) {
            $notifications[] = $this->createTestNotification((string) $notificationId);
        }

        return $notifications;
    }

    /**
     * @param array<int, PaymentNotificationV1> $notifications
     */
    private function enqueueNotifications(Queues $queuesClient, array $notifications): void
    {
        /** @psalm-suppress InvalidArgument */
        /** @psalm-suppress InvalidCast */
        foreach ($notifications as $notification) {
            /** @phpstan-ignore-next-line argument.type */
            $queuesClient->writeMessageToQueue('notifications', $notification);
        }
    }

    /**
     * @return array<string, array<string, array<string, int>>>
     */
    private function maxMessagesConfig(int $maxMessages): array
    {
        return [
            'queues' => [
                'notifications' => [
                    'MaxNumberOfMessages' => $maxMessages,
                ],
            ],
        ];
    }
}
