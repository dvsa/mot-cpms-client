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
    protected int $version = 1;
    protected ?string $clientId = null;
    protected ?string $clientSecret = null;
    protected ?string $userId = null;
    protected array $endPoints = [];
    protected ?string $customerReference = null;
    protected ?string $grantType = null;
    protected int $timeout = 30;
    /**
     * Payment Service domain
     */
    protected string $domain = '';
    protected array $headers = [];

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): void
    {
        $this->version = $version;
    }

    public function setCustomerReference(string $aeIdentity): void
    {
        $this->customerReference = $aeIdentity;
    }

    public function getCustomerReference(): string|null
    {
        return $this->customerReference;
    }

    public function setEndPoints(array $endPoints): void
    {
        $this->endPoints = $endPoints;
    }

    public function getEndPoints(): array
    {
        return $this->endPoints;
    }

    public function setGrantType(string $grantType): void
    {
        $this->grantType = $grantType;
    }

    public function getGrantType(): string|null
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
     * @return string|null
     */
    public function getClientId(): string|null
    {
        return $this->clientId;
    }

    public function setClientSecret(string | null $clientSecret): void
    {
        $this->clientSecret = $clientSecret;
    }

    public function getClientSecret(): string | null
    {
        return $this->clientSecret;
    }

    /**
     * @param string $userId
     * @return void
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return null|string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }
}
