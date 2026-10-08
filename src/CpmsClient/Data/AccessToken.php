<?php

declare(strict_types=1);

namespace CpmsClient\Data;

use Laminas\Stdlib\AbstractOptions;

/**
 * @extends AbstractOptions<mixed>
 */
class AccessToken extends AbstractOptions
{
    public const INVALID_ACCESS_TOKEN = 114;
    protected string|int $expiresIn;
    protected string $tokenType;
    protected string $accessToken;
    protected string $scope;
    protected int $issuedAt;
    protected string $salesReference;

    public function __construct($options = null)
    {
        $this->__strictMode__ = false;
        parent::__construct($options);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setIssuedAt(int $issuedAt): void
    {
        $this->issuedAt = $issuedAt;
    }

    public function getIssuedAt(): int
    {
        return $this->issuedAt;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setExpiresIn(int|string $expiresIn): void
    {
        $this->expiresIn = $expiresIn;
    }

    public function getExpiresIn(): int|string
    {
        return $this->expiresIn;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setScope(string $scope): void
    {
        $this->scope = $scope;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setTokenType(string $tokenType): void
    {
        $this->tokenType = $tokenType;
    }

    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    public function isExpired(): bool
    {
        $expiryTime = $this->getIssuedAt() + (int) $this->getExpiresIn();

        return ($expiryTime < time());
    }

    public function getAuthorisationHeader(): string
    {
        return 'Bearer ' . $this->getAccessToken();
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setSalesReference(string $salesReference): void
    {
        $this->salesReference = $salesReference;
    }
}
