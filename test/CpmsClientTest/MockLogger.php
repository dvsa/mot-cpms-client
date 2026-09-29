<?php

namespace CpmsClientTest;

use DvsaLogger\Logger\MotLogger;
use Monolog\Level;

class MockLogger extends MotLogger
{
    public array $logs = [];

    public function emerg(string $message, array $context = []): MotLogger
    {
        $this->log(Level::Emergency, $message, $context);
        return $this;
    }

    public function alert(string $message, array $context = []): MotLogger
    {
        $this->log(Level::Alert, $message, $context);
        return $this;
    }

    public function crit(string $message, array $context = []): MotLogger
    {
        $this->log(Level::Critical, $message, $context);
        return $this;
    }

    public function error(string $message, array $context = []): MotLogger
    {
        $this->log(Level::Error, $message, $context);
        return $this;
    }

    public function warn(string $message, array $context = []): MotLogger
    {
        $this->log(Level::Warning, $message, $context);
        return $this;
    }

    public function notice(string $message, array $context = []): MotLogger
    {
        $this->log(Level::Notice, $message, $context);
        return $this;
    }

    public function info(string $message, array $context = []): MotLogger
    {
        $this->log(Level::Info, $message, $context);
        return $this;
    }

    public function debug(string $message, array $context = []): MotLogger
    {
        $this->log(Level::Debug, $message, $context);
        return $this;
    }

    public function log($level, string $message, array $context = []): MotLogger
    {
        $this->logs[] = [
            'level' => $level,
            'message' => (string) $message,
            'context' => $context,
        ];
        return $this;
    }
}
