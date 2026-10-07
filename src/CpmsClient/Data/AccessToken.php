<?php

namespace CpmsClient\Data;

use Laminas\Stdlib\AbstractOptions;

/**
 * Class AccessToken
 *
 * @package CpmsClient\Data
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
     * @param int $issuedAt
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setIssuedAt($issuedAt): void
    {
        $this->issuedAt = $issuedAt;
    }

    /**
     * @return int
     */
    public function getIssuedAt(): int
    {
        return $this->issuedAt;
    }

    /**
     * @param string $accessToken
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setAccessToken($accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * @param string|int $expiresIn
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setExpiresIn($expiresIn): void
    {
        $this->expiresIn = $expiresIn;
    }

    /**
     * @return int|string
     */
    public function getExpiresIn(): int|string
    {
        return $this->expiresIn;
    }

    /**
     * @param string $scope
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setScope($scope): void
    {
        $this->scope = $scope;
    }

    /**
     * @return string
     */
    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * @param string $tokenType
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setTokenType($tokenType): void
    {
        $this->tokenType = $tokenType;
    }

    /**
     * @return string
     */
    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    /**
     * Is token expired
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        $expiryTime = (int) $this->getIssuedAt() + (int) $this->getExpiresIn();

        return ($expiryTime < time());
    }

    /**
     * Get Auth Header
     *
     * @return string
     */
    public function getAuthorisationHeader(): string
    {
        return 'Bearer ' . $this->getAccessToken();
    }

    /**
     * @param string $salesReference
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setSalesReference($salesReference): void
    {
        $this->salesReference = $salesReference;
    }
}
