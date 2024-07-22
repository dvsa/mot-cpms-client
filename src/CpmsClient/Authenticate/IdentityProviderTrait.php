<?php

namespace CpmsClient\Authenticate;

/**
 * Class IdentityProviderTrait
 * CPMS API Identity provider trait
 *
 * @package CpmsClient\Authenticate
 */
trait IdentityProviderTrait
{
    /** @var  string */
    protected $userId;
    /** @var  string */
    protected $clientId;
    /** @var  string */
    protected $clientSecret;
    /** @var  string */
    protected $customerReference;
    /** @var  string */
    protected $costCentre;
    /** @var ?int */
    protected $version = null;

    /**
     * @return string
     */
    public function getCostCentre()
    {
        return $this->costCentre;
    }

    /**
     * @param string $costCentre
     *
     * @return $this
     */
    public function setCostCentre($costCentre)
    {
        $this->costCentre = $costCentre;

        return $this;
    }

    /**
     * @return string
     */
    public function getCustomerReference()
    {
        return $this->customerReference;
    }

    /**
     * @param string $customerReference
     *
     * @return void
     */
    public function setCustomerReference($customerReference)
    {
        $this->customerReference = $customerReference;
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     *
     * @return void
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * @param string $clientSecret
     *
     * @return void
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     *
     * @return void
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return ?int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param ?int $version
     *
     * @return void
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }
}
