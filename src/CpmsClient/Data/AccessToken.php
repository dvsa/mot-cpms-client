<?php
namespace CpmsClient\Data;

use Laminas\Stdlib\AbstractOptions;
use Traversable;

/**
 * Class AccessToken
 * @extends AbstractOptions<array>
 *
 * @package CpmsClient\Data
 */
class AccessToken extends AbstractOptions
{
    const INVALID_ACCESS_TOKEN = 114;
    protected ?int $expiresIn = null;
    protected ?string $tokenType = null;
    protected ?string $accessToken = null;
    protected ?string $scope = null;
    protected ?int $issuedAt = null;
    protected ?string $salesReference = null;

    public function __construct(?iterable $options = null)
    {
        $this->__strictMode__ = false;
        parent::__construct($options);
    }

    public function setIssuedAt(int $issuedAt): void
    {
        $this->issuedAt = $issuedAt;
    }

    public function getIssuedAt(): int | null
    {
        return $this->issuedAt;
    }

    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getAccessToken(): string | null
    {
        return $this->accessToken;
    }

    public function setExpiresIn(int $expiresIn): void
    {
        $this->expiresIn = $expiresIn;
    }

    public function getExpiresIn(): int | null
    {
        return $this->expiresIn;
    }

    public function setScope(string $scope): void
    {
        $this->scope = $scope;
    }

    public function getScope(): string | null
    {
        return $this->scope;
    }

    public function setTokenType(string $tokenType): void
    {
        $this->tokenType = $tokenType;
    }

    public function getTokenType(): string | null
    {
        return $this->tokenType;
    }

    /**
     * Is token expired
     */
    public function isExpired(): bool
    {
        $expiryTime = (int)$this->getIssuedAt() + $this->getExpiresIn();

        return ($expiryTime < time());
    }

    /**
     * Get Auth Header
     */
    public function getAuthorisationHeader(): string
    {
        return 'Bearer ' . $this->getAccessToken();
    }

    public function setSalesReference(?string $salesReference): void
    {
        $this->salesReference = $salesReference;
    }
}
