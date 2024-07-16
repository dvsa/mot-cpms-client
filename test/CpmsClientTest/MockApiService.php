<?php

namespace CpmsClientTest;

use AllowDynamicProperties;
use CpmsClient\Data\AccessToken;
use CpmsClient\Service\ApiService;

/**
 * Class TestApiService
 *
 * @package CpmsClientTest
 */
class MockApiService extends ApiService
{
    protected bool $done = false;

    protected bool $forceRetry = false;

    protected int $expiresIn = 1;

    public function getTokenForScope(string $scope, null | string $salesReference = null): array | AccessToken | string
    {
        $token = parent::getTokenForScope($scope, $salesReference);

        if (isset($token) === false || $token === '' || $token === '0' || $token === []) {
            $token = $this->simulateToken($scope);
        }
        return $token;
    }

    /**
     * Make api request to get access token
     */
    protected function getPaymentServiceAccessToken(string $scope, null | string $salesReference = null): mixed
    {
        $data = parent::getPaymentServiceAccessToken($scope, $salesReference);
        if (!$this->done) {
            $this->done = true;
            return $data;
        }
        return $this->simulateToken($scope)->toArray();
    }

    private function simulateToken(string $scope): AccessToken
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

    public function isCacheDeletedFromRemote(array $return): bool
    {
        if ($this->forceRetry) {
            $this->forceRetry = false;
            return true;
        } else {
            return parent::isCacheDeletedFromRemote($return);
        }
    }

    public function setExpiresIn(int $value): void
    {
        $this->expiresIn = $value;
    }

    public function setForceRetry(): void
    {
        $this->forceRetry = true;
    }
}
