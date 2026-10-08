<?php

declare(strict_types=1);

namespace CpmsClient\Exceptions;

use Exception;

/**
 * this exception is thrown when an attempted notification acknowledgement
 * fails
 */
class CpmsNotificationAcknowledgementFailed extends Exception
{
    public function __construct(string $message, mixed $response)
    {
        $message = $message . "; response is: " . print_r($response, true);
        parent::__construct($message, 500);
    }
}
