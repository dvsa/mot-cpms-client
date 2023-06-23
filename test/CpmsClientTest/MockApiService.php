<?php
namespace CpmsClientTest;

use CpmsClient\Data\AccessToken;
use CpmsClient\Service\ApiService;

/**
 * Class TestApiService
 *
 * @package CpmsClientTest
 */
class MockApiService extends ApiService
{

    protected $done = false;

    protected $forceRetry = false;

    protected $expiresIn = 1;

    public function getTokenForScope($scope, $salesReference = null)
    {
        $token = parent::getTokenForScope($scope, $salesReference);

        if (empty($token)) {
            $token = $this->simulateToken($scope);
        }
        return $token;
    }

    /**
     * Make api request to get access token
     *
     * @param $scope
     * @param $salesReference
     *
     * @return mixed
     */
    protected function getPaymentServiceAccessToken($scope, $salesReference = null)
    {
        $data = parent::getPaymentServiceAccessToken($scope, $salesReference);
        if (!$this->done) {
            $this->done = true;
            return $data;
        }
        return $this->simulateToken($scope)->toArray();
    }

    /**
     * @param $scope
     *
     * @return AccessToken
     */
    private function simulateToken($scope)
    {
        $data = array(
            'issued_at'    => time(),
            'access_token' => md5('test'),
            'expires_in'   => $this->expiresIn,
            'scope'        => $scope,
            'token_type'   => 'Bearer'
        );
        return new AccessToken($data);
    }

    public function isCacheDeletedFromRemote($return)
    {
        if ($this->forceRetry) {
            $this->forceRetry = false;
            return true;
        } else {
            return parent::isCacheDeletedFromRemote($return);
        }
    }

    public function setExpiresIn($value)
    {
        $this->expiresIn = $value;
    }

    public function setForceRetry()
    {
        $this->forceRetry = true;
    }
}
