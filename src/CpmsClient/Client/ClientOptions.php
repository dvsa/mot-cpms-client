<?php

namespace CpmsClient\Client;

use Laminas\Stdlib\AbstractOptions;

/**
 * Class ClientOptions
 * @extends AbstractOptions<iterable>
 *
 * @package CpmsClient\Client
 */
class ClientOptions extends AbstractOptions
{
    /** @var int */
    protected $version = 1;
    /** @var  ?string */
    protected $clientId = null;
    /** @var  ?string */
    protected $clientSecret = null;
    /** @var  ?string */
    protected $userId = null;
    /** @var  array */
    protected $endPoints = [];
    /** @var  ?string */
    protected $customerReference = null;
    /** @var  ?string */
    protected $grantType = null;
    /** @var int */
    protected int $timeout = 30;
    /**
     * Payment Service domain
     * @var string
     */
    protected $domain = '';
    /** @var  array */
    protected array $headers = [];

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     *
     * @return void
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
     *
     * @return void
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @param ?string $aeIdentity
     *
     * @return void
     */
    public function setCustomerReference($aeIdentity)
    {
        $this->customerReference = $aeIdentity;
    }

    /**
     * @return ?string
     */
    public function getCustomerReference()
    {
        return $this->customerReference;
    }

    /**
     * @param array $endPoints
     *
     * @return void
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
     * @param ?string $grantType
     *
     * @return void
     */
    public function setGrantType($grantType)
    {
        $this->grantType = $grantType;
    }

    /**
     * @return ?string
     */
    public function getGrantType()
    {
        return $this->grantType;
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
     * @return ?string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param ?string $clientSecret
     *
     * @return void
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * @return ?string
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * @param ?string $userId
     *
     * @return void
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return ?string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $domain
     *
     * @return void
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
     *
     * @return void
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
