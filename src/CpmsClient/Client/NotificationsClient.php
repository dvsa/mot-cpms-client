<?php

namespace CpmsClient\Client;

use DVSA\CPMS\Queues\QueueAdapters\Interfaces\Queues;
use DVSA\CPMS\Queues\QueueAdapters\Values\QueueMessage;
use Laminas\Log\LoggerInterface;
use RuntimeException;
use Laminas\Log\Logger;

class NotificationsClient
{
    /**
     * in our config, what is the name of the queue we need to read
     * new notifications from?
     */
    protected const NOTIFICATIONS_QUEUENAME = "notifications";

    /**
     * our client for talking to our queues
     */
    protected Queues $queuesClient;

    protected LoggerInterface | Logger $logger;

    public function __construct(Queues $queuesClient, LoggerInterface $logger)
    {
        $this->queuesClient = $queuesClient;
        $this->logger = $logger;
    }

    /**
     * get the next batch of messages from the notifications queue
     *
     * if there are no messages, this will return an empty list
     */
    public function getNotifications(): array
    {
        // shorthand
        $queuesClient = $this->queuesClient;

        // what are we doing?
        $this->logger->debug("reading messages from queue: " . self::NOTIFICATIONS_QUEUENAME);

        // get our next messages
        $qMessages = $queuesClient->receiveMessagesFromQueue(self::NOTIFICATIONS_QUEUENAME);

        // decode them
        $notificationsArray = [];
        foreach ($qMessages as $qMessage) {
            // robustness!
            $notification = $qMessage->getPayload();
            if (!is_object($notification)) {
                throw new RuntimeException("non-object received from notifications queue");
            }
            $notificationsArray[] = [
                "metadata" => $qMessage,
                "message" => $notification,
            ];
        }

        // all done
        $this->logger->debug("read " . count($notificationsArray) . " message(s) from queue: " . self::NOTIFICATIONS_QUEUENAME);
        return $notificationsArray;
    }

    /**
     * confirm that a message can be dropped from the queue that it
     * came from
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
