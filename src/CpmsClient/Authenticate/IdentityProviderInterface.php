<?php

declare(strict_types=1);

namespace CpmsClient\Authenticate;

/**
 * Interface IdentityProviderInterface
 *
 * @package CpmsClient\Authenticate
 */
interface IdentityProviderInterface
{
    /**
     * OAuth 2.0 client_id
     *
     * @return string
     */
    public function getClientId(): string;

    /**
     * OAuth 2.0 client_secret
     *
     * @return string
     */
    public function getClientSecret(): string;

    /**
     * Logged in user (OpenAM UUID)
     *
     * @return string
     */
    public function getUserId(): string;

    /**
     * Get the reference to the customer the payment is for
     *
     * @return mixed
     */
    public function getCustomerReference(): mixed;

    /**
     * @return string
     */
    public function getCostCentre(): string;
}
