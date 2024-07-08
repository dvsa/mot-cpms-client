<?php

namespace CpmsClient\Client;

use Exception;
use Laminas\Stdlib\AbstractOptions;

/**
 * Class ClientOptions
 * @extends AbstractOptions<iterable>
 *
 * @package CpmsClient\Client
 */
class ClientOptions extends AbstractOptions
{
    /** @var int $version */
    protected $version = 1;
    /** @var  string $clientId */
    protected $clientId;
    /** @var  string $clientSecret */
    protected $clientSecret;
    /** @var  string $userId */
    protected $userId;
    /** @var array $endPoints */
    protected $endPoints = [];
    /** @var  string $customerReference */
    protected $customerReference;
    /** @var  string $grantType */
    protected $grantType;
    /** @var int $timeout */
    protected $timeout = 30;

    /**
     * Payment Service domain
     *
     * @var string $domain
     */
    protected $domain;

    /**
     * @var array $headers
     */
    protected $headers = array();

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout($timeout): void
    {
        $this->timeout = $timeout;
    }

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @param int $version
     */
    public function setVersion($version): void
    {
        $this->version = $version;
    }

    /**
     * @param string $aeIdentity
     */
    public function setCustomerReference($aeIdentity): void
    {
        $this->customerReference = $aeIdentity;
    }

    /**
     * @return string
     */
    public function getCustomerReference(): string
    {
        return $this->customerReference;
    }

    /**
     * @param array $endPoints
     */
    public function setEndPoints($endPoints): void
    {
        $this->endPoints = $endPoints;
    }

    /**
     * @return array
     */
    public function getEndPoints(): array
    {
        return $this->endPoints;
    }

    /**
     * @param string $grantType
     */
    public function setGrantType($grantType): void
    {
        $this->grantType = $grantType;
    }

    /**
     * @return string
     */
    public function getGrantType(): string
    {
        return $this->grantType;
    }

    /**
     * @param string $clientId
     */
    public function setClientId($clientId): void
    {
        $this->clientId = $clientId;
    }

    /**
     * @return string
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * @param string $clientSecret
     */
    public function setClientSecret($clientSecret): void
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * @return string
     */
    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    /**
     * @param string $userId
     */
    public function setUserId($userId): void
    {
        $this->userId = $userId;
    }

    /**
     * @return string
     */
    public function getUserId(): string
    {
        return $this->userId;
    }


    /**
     * @param string $domain
     */
    public function setDomain($domain): void
    {
        $this->domain = $domain;
    }

    /**
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * @param array $headers
     */
    public function setHeaders($headers): void
    {
        $this->headers = $headers;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
