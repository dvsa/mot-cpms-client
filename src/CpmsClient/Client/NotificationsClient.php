<?php

declare(strict_types=1);

namespace CpmsClient\Client;

use DVSA\CPMS\Queues\QueueAdapters\Interfaces\Queues;
use DVSA\CPMS\Queues\QueueAdapters\Values\QueueMessage;
use DvsaLogger\Logger\MotLogger;
use RuntimeException;

class NotificationsClient
{
    public const NOTIFICATIONS_QUEUE_NAME = "notifications";

    public function __construct(
        private readonly Queues $queuesClient,
        private readonly MotLogger $logger
    ) {
    }

    /**
     * Get the next batch of messages from the notifications queue
     * if there are no messages, this will return an empty list.
     *
     * @return array<int, array{metadata: QueueMessage, message: object}>
     */
    public function getNotifications(): array
    {

        $queuesClient = $this->queuesClient;
        $this->logger->debug("[" . NotificationsClient::class . "]: Reading messages from queue: " . self::NOTIFICATIONS_QUEUE_NAME);

        /** @var QueueMessage[] $qMessages */
        $qMessages = $queuesClient->receiveMessagesFromQueue(self::NOTIFICATIONS_QUEUE_NAME);

        /** @var array<int, array{metadata: QueueMessage, message: object}> $notificationsArray */
        $notificationsArray = [];
        foreach ($qMessages as $qMessage) {
            $notification = $qMessage->getPayload();

            if (!is_object($notification)) {
                $this->logger->warn("[" . NotificationsClient::class . "]: Non-object received from notifications queue");
                throw new RuntimeException("non-object received from notifications queue");
            }

            $notificationsArray[] = [
                "metadata" => $qMessage,
                "message" => $notification,
            ];
        }

        $this->logger->debug("[" . NotificationsClient::class . "]: Read " . count($notificationsArray) . " message(s) from queue: " . self::NOTIFICATIONS_QUEUE_NAME);
        return $notificationsArray;
    }

    public function confirmMessageHandled(QueueMessage $metadata): void
    {
        $this->queuesClient->confirmMessageHandled($metadata);
    }

    public function getQueuesClient(): Queues
    {
        return $this->queuesClient;
    }
}
