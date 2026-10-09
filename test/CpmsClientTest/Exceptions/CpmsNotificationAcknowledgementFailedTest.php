<?php

declare(strict_types=1);

namespace CpmsClientTest\Exceptions;

use CpmsClient\Exceptions\CpmsNotificationAcknowledgementFailed;
use Exception;
use PHPUnit\Framework\TestCase;

class CpmsNotificationAcknowledgementFailedTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $message = "created during unit test";
        $response = [ 'message' => 'this is a unit test' ];

        $unit = new CpmsNotificationAcknowledgementFailed($message, $response);

        $this->assertInstanceOf(CpmsNotificationAcknowledgementFailed::class, $unit);
    }

    public function testIsException(): void
    {
        $message = "created during unit test";
        $response = [ 'message' => 'this is a unit test' ];

        $unit = new CpmsNotificationAcknowledgementFailed($message, $response);
        $this->assertInstanceOf(Exception::class, $unit);
    }
}
