<?php namespace CpmsClientTest;

use Laminas\Log\LoggerInterface;

class MockLogger implements LoggerInterface
{
    public function emerg($message, $extra = [])
    {
    }

    public function alert($message, $extra = [])
    {
    }

    public function crit($message, $extra = [])
    {
    }

    public function err($message, $extra = [])
    {
    }

    public function warn($message, $extra = [])
    {
    }

    public function notice($message, $extra = [])
    {
    }

    public function info($message, $extra = [])
    {
    }

    public function debug($message, $extra = [])
    {
    }
}
