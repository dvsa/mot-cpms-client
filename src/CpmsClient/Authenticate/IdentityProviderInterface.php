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
     *
     * @return string
     */
    public function getClientId();

    /**
     * OAuth 2.0 client_secret
     *
     * @return string
     */
    public function getClientSecret();

    /**
     * Logged in user (OpenAM UUID)
     *
     * @return string
     */
    public function getUserId();

    /**
     * Get the reference to the customer the payment is for
     *
     * @return mixed
     */
    public function getCustomerReference();

    /** string */
    public function getCostCentre();
}
