<?php

namespace CpmsClient\Exceptions;

use Exception;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass CpmsClient\Exceptions\CpmsNotificationAcknowledgementFailed
 */
class CpmsNotificationAcknowledgementFailedTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testCanInstantiate()
    {
        // ----------------------------------------------------------------
        // setup your test

        $message = "created during unit test";
        $response = [ 'message' => 'this is a unit test' ];

        // ----------------------------------------------------------------
        // perform the change

        $unit = new CpmsNotificationAcknowledgementFailed($message, $response);

        // ----------------------------------------------------------------
        // test the results

        $this->assertInstanceOf(CpmsNotificationAcknowledgementFailed::class, $unit);
    }

    /**
     * @covers ::__construct
     */
    public function testIsException()
    {
        // ----------------------------------------------------------------
        // setup your test

        $message = "created during unit test";
        $response = [ 'message' => 'this is a unit test' ];

        // ----------------------------------------------------------------
        // perform the change

        $unit = new CpmsNotificationAcknowledgementFailed($message, $response);

        // ----------------------------------------------------------------
        // test the results

        $this->assertInstanceOf(Exception::class, $unit);
    }

}