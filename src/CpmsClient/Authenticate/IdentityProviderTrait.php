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
    /** @var null | int */
    protected $version = null;

    /**
     * @return string
     */
    public function getCostCentre()
    {
        return $this->costCentre;
    }

    /**
     * @param $costCentre
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
     */
    public function setCustomerReference($customerReference)
    {
        $this->customerReference = $customerReference;
    }

    /**
     * @return mixed
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param mixed $clientId
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @return mixed
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * @param mixed $clientSecret
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param mixed $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return int|null
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param int|null $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }
}
