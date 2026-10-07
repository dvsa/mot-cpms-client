<?php

declare(strict_types=1);

namespace CpmsClient\Authenticate;

/**
 * Class IdentityProviderTrait
 * CPMS API Identity provider trait
 *
 * @package CpmsClient\Authenticate
 */
trait IdentityProviderTrait
{
    protected string $userId;
    protected string $clientId;
    protected string $clientSecret;
    protected string $customerReference;
    protected string $costCentre;
    protected ?int $version = null;

    public function getCostCentre(): string
    {
        return $this->costCentre;
    }

    public function setCostCentre(string $costCentre): static
    {
        $this->costCentre = $costCentre;

        return $this;
    }

    public function getCustomerReference(): string
    {
        return $this->customerReference;
    }

    public function setCustomerReference(string $customerReference): void
    {
        $this->customerReference = $customerReference;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function setClientSecret(string $clientSecret): void
    {
        $this->clientSecret = $clientSecret;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function getVersion(): ?int
    {
        return $this->version;
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function setVersion(?int $version): void
    {
        $this->version = $version;
    }
}
