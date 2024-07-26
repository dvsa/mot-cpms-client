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
    const NOTIFICATIONS_QUEUENAME = "notifications";

    /**
     * our constructor
     *
     * @param Queues $queuesClient
     *        how we will talk to our queues
     * @param Logger $logger
     *        how we will report on what happens
     */
    public function __construct(
        protected Queues $queuesClient,
        public LoggerInterface $logger
    ) {
    }

    /**
     * get the next batch of messages from the notifications queue
     *
     * if there are no messages, this will return an empty list
     *
     * @return array
     */
    public function getNotifications()
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
     *
     * @param  QueueMessage $metadata
     *         the metadata message that we're done with
     * @return void
     */
    public function confirmMessageHandled(QueueMessage $metadata)
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
     *
     * @return Queues
     */
    public function getQueuesClient()
    {
        return $this->queuesClient;
    }
}
