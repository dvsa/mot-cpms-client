<?php

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
     */
    public function getClientId(): string;

    /**
     * OAuth 2.0 client_secret
     */
    public function getClientSecret(): string;

    /**
     * Logged in user (OpenAM UUID)
     */
    public function getUserId(): string;

    /**
     * Get the reference to the customer the payment is for
     */
    public function getCustomerReference(): mixed;

    /** string */
    public function getCostCentre(): string;
}
