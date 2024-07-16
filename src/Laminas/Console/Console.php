<?php

namespace Laminas\Console;

// Ugly workaround to get integration tests working...
// We are not using Console requests - this is only to allow tests to run with PHP 8.
// Remove once laminas/laminas-test 4.0.X is released.
class Console
{
    /**
     * @return false
     */
    public static function isConsole(): bool
    {
        return false;
    }

    public static function overrideIsConsole(mixed $param): void
    {
        return;
    }
}
