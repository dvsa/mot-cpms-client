<?php

namespace CpmsClientTest\Client;

use CpmsClient\Client\NotificationsClient;
use DateTime;
use DVSA\CPMS\Notifications\Ids\ValueBuilders\GenerateNotificationId;
use DVSA\CPMS\Notifications\Messages\Maps\MapNotificationTypes;
use DVSA\CPMS\Notifications\Messages\Values\PaymentNotificationV1;
use DVSA\CPMS\Queues\QueueAdapters\InMemory\InMemoryQueues;
use DVSA\CPMS\Queues\QueueAdapters\Interfaces\Queues;
use PHPUnit\Framework\TestCase;
use Laminas\Log\Logger;
use Laminas\Log\Writer\Mock as MockWriter;

/**
 * @coversDefaultClass CpmsClient\Client\NotificationsClient
 */
class NotificationsClientTest extends TestCase
{
    protected $backupStaticAttributes = null;
    protected $runTestInSeparateProcess = null;

    /**
     * @covers ::__construct
     */
    public function testCanInstantiate(): void
    {
        // ----------------------------------------------------------------
        // setup your test

        // ----------------------------------------------------------------
        // perform the change

        $unit = $this->provideNotificationsClient();

        // ----------------------------------------------------------------
        // test the results

        $this->assertInstanceOf(NotificationsClient::class, $unit);
    }

    protected function provideNotificationsClient(array $extraConfig = []): NotificationsClient
    {
        // our default config has a single, active queue
        $queuesConfig = [
            'queues' => [
                'notifications' => [
                    'active' => true,
                    'Middleware' => [
                        'MultipartMessage' => [
                            'mapper' => MapNotificationTypes::class,
                        ]
                    ]
                ]
            ]
        ];

        // merge in any extra config that you need for your test
        $queuesConfig = array_merge_recursive($queuesConfig, $extraConfig);

        // build a queues client from our final config
        $queues = new InMemoryQueues($queuesConfig);

        // the NotificationsClient needs a logger
        //
        // we use ZF2's mock writer, so that the logger never attempts to
        // write either to disk nor to the screen
        $logger = new Logger;
        $mockWriter = new MockWriter;
        $logger->addWriter($mockWriter);

        // finally!! we can build the client that we're unit testing here
        $unit = new NotificationsClient($queues, $logger);

        return $unit;
    }

    /**
     * @covers ::getNotifications
     */
    public function testCanGetNotificationsFromQueue(): void
    {
        // ----------------------------------------------------------------
        // setup your test

        $unit = $this->provideNotificationsClient();
        $queuesClient = $unit->getQueuesClient();

        // we need to put a message onto this queue
        $expectedNotification = new PaymentNotificationV1(
            'unit-test',
            GenerateNotificationId::now(),
            new DateTime('2015-01-01 00:30:00 +0000'),
            'CPMS',
            'unit-test',
            'test',
            'unit-test',
            new DateTime('2015-01-01 00:00:00 +0000'),
            'CPMS-123456-67890',
            3.14
        );
        /** @psalm-suppress InvalidArgument */
        $queuesClient->writeMessageToQueue('notifications', $expectedNotification);

        // ----------------------------------------------------------------
        // perform the change

        $actualNotifications = $unit->getNotifications();

        // ----------------------------------------------------------------
        // test the results

        // make sure that we have a result at all!
        $this->assertCount(1, $actualNotifications);

        // make sure that the first entry in the list is what we expect
        $this->assertArrayHasKey(0, $actualNotifications);
        $this->assertArrayHasKey('message', $actualNotifications[0]);
        $this->assertEquals($expectedNotification, $actualNotifications[0]['message']);
    }

    /**
     * @covers ::getNotifications
     */
    public function testReturnsEmptyListWhenNoNotificationsAvailable(): void
    {
        // ----------------------------------------------------------------
        // setup your test

        $unit = $this->provideNotificationsClient();

        // ----------------------------------------------------------------
        // perform the change

        $actualNotifications = $unit->getNotifications();

        // ----------------------------------------------------------------
        // test the results

        $this->assertCount(0, $actualNotifications);
    }

    /**
     * @covers ::getNotifications
     */
    public function testCanReturnMultipleNotifications(): void
    {
        // ----------------------------------------------------------------
        // setup your test

        $extraConfig = [
            'queues' => [
                'notifications' => [
                    'MaxNumberOfMessages' => 5,
                ]
            ]
        ];
        $unit = $this->provideNotificationsClient($extraConfig);
        $queuesClient = $unit->getQueuesClient();

        // we need to put several messages onto this queue
        $expectedNotifications = [
            new PaymentNotificationV1(
                'unit-test',
                '1',
                new DateTime('2015-01-01 00:30:00 +0000'),
                'CPMS',
                'unit-test',
                'test',
                'unit-test',
                new DateTime('2015-01-01 00:00:00 +0000'),
                'CPMS-123456-67890',
                3.14
            ),
            new PaymentNotificationV1(
                'unit-test',
                '2',
                new DateTime('2015-01-01 00:30:00 +0000'),
                'CPMS',
                'unit-test',
                'test',
                'unit-test',
                new DateTime('2015-01-01 00:00:00 +0000'),
                'CPMS-123456-67890',
                3.14
            ),
            new PaymentNotificationV1(
                'unit-test',
                '3',
                new DateTime('2015-01-01 00:30:00 +0000'),
                'CPMS',
                'unit-test',
                'test',
                'unit-test',
                new DateTime('2015-01-01 00:00:00 +0000'),
                'CPMS-123456-67890',
                3.14
            ),
            new PaymentNotificationV1(
                'unit-test',
                '4',
                new DateTime('2015-01-01 00:30:00 +0000'),
                'CPMS',
                'unit-test',
                'test',
                'unit-test',
                new DateTime('2015-01-01 00:00:00 +0000'),
                'CPMS-123456-67890',
                3.14
            ),
            new PaymentNotificationV1(
                'unit-test',
                '5',
                new DateTime('2015-01-01 00:30:00 +0000'),
                'CPMS',
                'unit-test',
                'test',
                'unit-test',
                new DateTime('2015-01-01 00:00:00 +0000'),
                'CPMS-123456-67890',
                3.14
            ),
        ];
        foreach ($expectedNotifications as $expectedNotification) {
            /** @psalm-suppress InvalidArgument */
            $queuesClient->writeMessageToQueue('notifications', $expectedNotification);
        }

        // ----------------------------------------------------------------
        // perform the change

        $actualNotifications = $unit->getNotifications();

        // ----------------------------------------------------------------
        // test the results

        $this->assertCount(5, $actualNotifications);

        // InMemoryQueues guarantees ordering, which makes the following
        // key-based checks safe
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
        // ----------------------------------------------------------------
        // setup your test

        $extraConfig = [
            'queues' => [
                'notifications' => [
                    'MaxNumberOfMessages' => 2,
                ]
            ]
        ];
        $unit = $this->provideNotificationsClient($extraConfig);
        $queuesClient = $unit->getQueuesClient();

        // we need to put several messages onto this queue
        $expectedNotifications = [
            new PaymentNotificationV1(
                'unit-test',
                '1',
                new DateTime('2015-01-01 00:30:00 +0000'),
                'CPMS',
                'unit-test',
                'test',
                'unit-test',
                new DateTime('2015-01-01 00:00:00 +0000'),
                'CPMS-123456-67890',
                3.14
            ),
            new PaymentNotificationV1(
                'unit-test',
                '2',
                new DateTime('2015-01-01 00:30:00 +0000'),
                'CPMS',
                'unit-test',
                'test',
                'unit-test',
                new DateTime('2015-01-01 00:00:00 +0000'),
                'CPMS-123456-67890',
                3.14
            ),
            new PaymentNotificationV1(
                'unit-test',
                '3',
                new DateTime('2015-01-01 00:30:00 +0000'),
                'CPMS',
                'unit-test',
                'test',
                'unit-test',
                new DateTime('2015-01-01 00:00:00 +0000'),
                'CPMS-123456-67890',
                3.14
            ),
            new PaymentNotificationV1(
                'unit-test',
                '4',
                new DateTime('2015-01-01 00:30:00 +0000'),
                'CPMS',
                'unit-test',
                'test',
                'unit-test',
                new DateTime('2015-01-01 00:00:00 +0000'),
                'CPMS-123456-67890',
                3.14
            ),
            new PaymentNotificationV1(
                'unit-test',
                '5',
                new DateTime('2015-01-01 00:30:00 +0000'),
                'CPMS',
                'unit-test',
                'test',
                'unit-test',
                new DateTime('2015-01-01 00:00:00 +0000'),
                'CPMS-123456-67890',
                3.14
            ),
        ];
        $mapper = new MapNotificationTypes;
        foreach ($expectedNotifications as $expectedNotification) {
            /** @psalm-suppress InvalidArgument */
            $queuesClient->writeMessageToQueue('notifications', $expectedNotification);
        }

        // ----------------------------------------------------------------
        // perform the change

        $actualNotifications = $unit->getNotifications();

        // ----------------------------------------------------------------
        // test the results

        $this->assertCount(2, $actualNotifications);

        // InMemoryQueues guarantees ordering, which makes the following
        // key-based checks safe
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
        // ----------------------------------------------------------------
        // setup your test
        //
        // this test depends on the behaviour of the underlying queue!

        $extraConfig = [
            'queues' => [
                'notifications' => [
                    'MaxNumberOfMessages' => 20,
                ]
            ]
        ];
        $unit = $this->provideNotificationsClient($extraConfig);
        $queuesClient = $unit->getQueuesClient();

        // we need to put several messages onto this queue
        $expectedNotifications = [
            new PaymentNotificationV1(
                'unit-test',
                '1',
                new DateTime('2015-01-01 00:30:00 +0000'),
                'CPMS',
                'unit-test',
                'test',
                'unit-test',
                new DateTime('2015-01-01 00:00:00 +0000'),
                'CPMS-123456-67890',
                3.14
            ),
            new PaymentNotificationV1(
                'unit-test',
                '2',
                new DateTime('2015-01-01 00:30:00 +0000'),
                'CPMS',
                'unit-test',
                'test',
                'unit-test',
                new DateTime('2015-01-01 00:00:00 +0000'),
                'CPMS-123456-67890',
                3.14
            ),
            new PaymentNotificationV1(
                'unit-test',
                '3',
                new DateTime('2015-01-01 00:30:00 +0000'),
                'CPMS',
                'unit-test',
                'test',
                'unit-test',
                new DateTime('2015-01-01 00:00:00 +0000'),
                'CPMS-123456-67890',
                3.14
            ),
            new PaymentNotificationV1(
                'unit-test',
                '4',
                new DateTime('2015-01-01 00:30:00 +0000'),
                'CPMS',
                'unit-test',
                'test',
                'unit-test',
                new DateTime('2015-01-01 00:00:00 +0000'),
                'CPMS-123456-67890',
                3.14
            ),
            new PaymentNotificationV1(
                'unit-test',
                '5',
                new DateTime('2015-01-01 00:30:00 +0000'),
                'CPMS',
                'unit-test',
                'test',
                'unit-test',
                new DateTime('2015-01-01 00:00:00 +0000'),
                'CPMS-123456-67890',
                3.14
            ),
        ];
        $mapper = new MapNotificationTypes;
        foreach ($expectedNotifications as $expectedNotification) {
            /** @psalm-suppress InvalidArgument */
            $queuesClient->writeMessageToQueue('notifications', $expectedNotification);
        }

        // ----------------------------------------------------------------
        // perform the change

        $actualNotifications = $unit->getNotifications();

        // ----------------------------------------------------------------
        // test the results

        $this->assertCount(5, $actualNotifications);

        // InMemoryQueues guarantees ordering, which makes the following
        // key-based checks safe
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
        // ----------------------------------------------------------------
        // setup your test

        $unit = $this->provideNotificationsClient();

        // ----------------------------------------------------------------
        // perform the change

        $actualClient = $unit->getQueuesClient();

        // ----------------------------------------------------------------
        // test the results

        $this->assertInstanceOf(Queues::class, $actualClient);
    }

}
