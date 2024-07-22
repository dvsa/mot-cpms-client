<?php

namespace CpmsClient\Data;

use Laminas\Stdlib\AbstractOptions;

/**
 * Class AccessToken
 * @extends AbstractOptions<iterable>
 *
 * @package CpmsClient\Data
 */
class AccessToken extends AbstractOptions
{
    public const INVALID_ACCESS_TOKEN = 114;
    /** @var  ?int */
    protected $expiresIn = null;
    /** @var  ?string */
    protected $tokenType = null;
    /** @var  ?string */
    protected $accessToken = null;
    /** @var  ?string */
    protected $scope = null;
    /** @var  ?int */
    protected $issuedAt = null;
    /** @var  ?string */
    protected $salesReference = null;

    public function __construct(?iterable $options = null)
    {
        $this->__strictMode__ = false;
        /**
         * @phpstan-ignore-next-line
         */
        parent::__construct($options);
    }

    /**
     * @param ?int $issuedAt
     *
     * @return void
     */
    public function setIssuedAt($issuedAt)
    {
        $this->issuedAt = $issuedAt;
    }

    /**
     * @return ?int
     */
    public function getIssuedAt()
    {
        return $this->issuedAt;
    }

    /**
     * @param ?string $accessToken
     *
     * @return void
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @return ?string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @param ?int $expiresIn
     *
     * @return void
     */
    public function setExpiresIn($expiresIn)
    {
        $this->expiresIn = $expiresIn;
    }

    /**
     * @return ?int
     */
    public function getExpiresIn()
    {
        return $this->expiresIn;
    }

    /**
     * @param ?string $scope
     *
     * @return void
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    /**
     * @return ?string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param ?string $tokenType
     *
     * @return void
     */
    public function setTokenType($tokenType)
    {
        $this->tokenType = $tokenType;
    }

    /**
     * @return ?string
     */
    public function getTokenType()
    {
        return $this->tokenType;
    }

    /**
     * Is token expired
     *
     * @return bool
     */
    public function isExpired()
    {
        $expiryTime = (int)$this->getIssuedAt() + $this->getExpiresIn();

        return ($expiryTime < time());
    }

    /**
     * Get Auth Header
     *
     * @return string
     */
    public function getAuthorisationHeader()
    {
        return 'Bearer ' . $this->getAccessToken();
    }

    /**
     * @param ?string $salesReference
     *
     * @return void
     */
    public function setSalesReference($salesReference)
    {
        $this->salesReference = $salesReference;
    }
}
