<?php

namespace CpmsClient\Exceptions;

use Exception;

/**
 * this exception is thrown when an attempted notification acknowledgement
 * fails
 */
class CpmsNotificationAcknowledgementFailed extends Exception
{
    /**
     * @param string $message why the response was rejected
     * @param mixed $response the rejected response
     */
    public function __construct($message, $response)
    {
        $message = $message . "; response is: " . print_r($response, true);
        parent::__construct($message, 500);
    }
}
