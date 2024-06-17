<?php namespace CpmsClientTest;

use Laminas\Log\LoggerInterface;

class MockLogger implements LoggerInterface
{
    /**
     * @param $message
     * @param $extra
     * @return void
     *
     * @psalm-suppress MissingParamType
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    public function emerg($message, $extra = []): void
    {
    }

    /**
     * @param $message
     * @param $extra
     * @return void
     *
     * @psalm-suppress MissingParamType
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    public function alert($message, $extra = []): void
    {
    }

    /**
     * @param $message
     * @param $extra
     * @return void
     *
     * @psalm-suppress MissingParamType
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    public function crit($message, $extra = [])
    {
    }

    /**
     * @param $message
     * @param $extra
     * @return void
     *
     * @psalm-suppress MissingParamType
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    public function err($message, $extra = [])
    {
    }

    /**
     * @param $message
     * @param $extra
     * @return void
     *
     * @psalm-suppress MissingParamType
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    public function warn($message, $extra = [])
    {
    }

    /**
     * @param $message
     * @param $extra
     * @return void
     *
     * @psalm-suppress MissingParamType
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    public function notice($message, $extra = [])
    {
    }

    /**
     * @param $message
     * @param $extra
     * @return void
     *
     * @psalm-suppress MissingParamType
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    public function info($message, $extra = [])
    {
    }

    /**
     * @param $message
     * @param $extra
     * @return void
     *
     * @psalm-suppress MissingParamType
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    public function debug($message, $extra = [])
    {
    }
}
