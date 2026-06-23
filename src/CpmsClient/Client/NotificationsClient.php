<?php

namespace CpmsClient\Client;

use DVSA\CPMS\Queues\QueueAdapters\Interfaces\Queues;
use DVSA\CPMS\Queues\QueueAdapters\Values\QueueMessage;
use Psr\Log\LoggerInterface;
use RuntimeException;

class NotificationsClient
{
    /**
     * In our config, what is the name of the queue we need to read
     * new notifications from?
     */
    const NOTIFICATIONS_QUEUENAME = "notifications";

    /**
     * @param Queues $queuesClient
     *        how we will talk to our queues
     * @param LoggerInterface $logger
     *        how we will report on what happens
     */
    public function __construct(
        private readonly Queues $queuesClient,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Get the next batch of messages from the notifications queue
     * if there are no messages, this will return an empty list.
     */
    public function getNotifications(): array
    {

        $queuesClient = $this->queuesClient;
        $this->logger->debug("reading messages from queue: " . self::NOTIFICATIONS_QUEUENAME);
        $qMessages = $queuesClient->receiveMessagesFromQueue(self::NOTIFICATIONS_QUEUENAME);
        $notificationsArray = [];
        foreach ($qMessages as $qMessage) {
            $notification = $qMessage->getPayload();
            if (!is_object($notification)) {
                throw new RuntimeException("non-object received from notifications queue");
            }
            $notificationsArray[] = [
                "metadata" => $qMessage,
                "message" => $notification,
            ];
        }

        $this->logger->debug("read " . count($notificationsArray) . " message(s) from queue: " . self::NOTIFICATIONS_QUEUENAME);
        return $notificationsArray;
    }

    /**
     * Confirm that a message can be dropped from the queue that it
     * came from.
     *
     * @param  QueueMessage $metadata
     *         the metadata message that we're done with
     */
    public function confirmMessageHandled(QueueMessage $metadata): void
    {
        $this->queuesClient->confirmMessageHandled($metadata);
    }

    // ==================================================================
    //
    // Helpers go here
    //
    // ------------------------------------------------------------------

    /**
     * returns the client we are using to talk to our queues
     *
     * mainly here to help with unit testing
     */
    public function getQueuesClient(): Queues
    {
        return $this->queuesClient;
    }
}
