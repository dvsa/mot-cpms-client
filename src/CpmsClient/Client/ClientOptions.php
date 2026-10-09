<?php

declare(strict_types=1);

namespace CpmsClient\Client;

use Laminas\Stdlib\AbstractOptions;

/**
 * @psalm-api
 * @extends AbstractOptions<mixed>
 */
class ClientOptions extends AbstractOptions
{
    protected int $version = 1;
    protected string $clientId;
    protected string $clientSecret;
    protected string $userId;
    /** @var array<string, string> */
    protected array $endPoints = [];
    protected string $customerReference;
    protected string $grantType;
    protected int $timeout = 30;

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

    public function getCustomerReference(): string
    {
        return $this->customerReference;
    }

    /**
     * @param array<string, string> $endPoints
     */
    public function setEndPoints(array $endPoints): void
    {
        $this->endPoints = $endPoints;
    }

    /**
     * @return array<string, string>
     */
    public function getEndPoints(): array
    {
        return $this->endPoints;
    }

    public function setGrantType(string $grantType): void
    {
        $this->grantType = $grantType;
    }

    public function getGrantType(): string
    {
        return $this->grantType;
    }

    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientSecret(string $clientSecret): void
    {
        $this->clientSecret = $clientSecret;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * Payment Service domain
     */
    protected string $domain;

    /** @var array<string, string> */
    protected array $headers = array();

    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * @param array<string, string> $headers
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
