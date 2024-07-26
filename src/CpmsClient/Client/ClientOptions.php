<?php
namespace CpmsClient\Client;

use Laminas\Stdlib\AbstractOptions;

/**
 * Class ClientOptions
 *
 * @package CpmsClient\Client
 */
class ClientOptions extends AbstractOptions
{
    /** @var int */
    protected $version = 1;
    /** @var  string */
    protected $clientId;
    /** @var  string */
    protected $clientSecret;
    /** @var  string */
    protected $userId;
    /** @var array */
    protected $endPoints = array();
    /** @var  string */
    protected $customerReference;
    /** @var  string */
    protected $grantType;
    /** @var int */
    protected $timeout = 30;

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param int $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @param string $aeIdentity
     */
    public function setCustomerReference($aeIdentity)
    {
        $this->customerReference = $aeIdentity;
    }

    /**
     * @return string
     */
    public function getCustomerReference()
    {
        return $this->customerReference;
    }

    /**
     * @param array $endPoints
     */
    public function setEndPoints($endPoints)
    {
        $this->endPoints = $endPoints;
    }

    /**
     * @return array
     */
    public function getEndPoints()
    {
        return $this->endPoints;
    }

    /**
     * @param string $grantType
     */
    public function setGrantType($grantType)
    {
        $this->grantType = $grantType;
    }

    /**
     * @return string
     */
    public function getGrantType()
    {
        return $this->grantType;
    }

    /**
     * @param string $clientId
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param string $clientSecret
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * @param string $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Payment Service domain
     *
     * @var string
     */
    protected $domain;

    /**
     * @var array
     */
    protected $headers = array();

    /**
     * @param string $domain
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @param array $headers
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}
