<?php
namespace CpmsClient\Data;

use Laminas\Stdlib\AbstractOptions;

/**
 * Class AccessToken
 *
 * @package CpmsClient\Data
 */
class AccessToken extends AbstractOptions
{
    const INVALID_ACCESS_TOKEN = 114;
    /**
     * @var string
     */
    protected $expiresIn;
    /** @var  string */
    protected $tokenType;
    /** @var  string */
    protected $accessToken;
    /** @var  string */
    protected $scope;
    /** @var  int */
    protected $issuedAt;
    /** @var  string */
    protected $salesReference;

    public function __construct($options = null)
    {
        $this->__strictMode__ = false;
        parent::__construct($options);
    }

    /**
     * @param int $issuedAt
     */
    public function setIssuedAt($issuedAt)
    {
        $this->issuedAt = $issuedAt;
    }

    /**
     * @return int
     */
    public function getIssuedAt()
    {
        return $this->issuedAt;
    }

    /**
     * @param string $accessToken
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @param string $expiresIn
     */
    public function setExpiresIn($expiresIn)
    {
        $this->expiresIn = $expiresIn;
    }

    /**
     * @return int
     */
    public function getExpiresIn()
    {
        return $this->expiresIn;
    }

    /**
     * @param string $scope
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param string $tokenType
     */
    public function setTokenType($tokenType)
    {
        $this->tokenType = $tokenType;
    }

    /**
     * @return string
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
     * @param string $salesReference
     */
    public function setSalesReference($salesReference)
    {
        $this->salesReference = $salesReference;
    }
}
