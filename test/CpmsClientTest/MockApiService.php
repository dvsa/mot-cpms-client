<?php

declare(strict_types=1);

namespace CpmsClientTest;

use CpmsClient\Data\AccessToken;
use CpmsClient\Service\ApiService;

class MockApiService extends ApiService
{
    protected bool $done = false;

    protected bool $forceRetry = false;

    protected int $expiresIn = 1;

    #[\Override]
    public function getTokenForScope(string $scope, ?string $salesReference = '')
    {
        $token = parent::getTokenForScope($scope, $salesReference);

        if (empty($token)) {
            $token = $this->simulateToken($scope);
        }
        return $token;
    }

    /**
     * Make api request to get access token
     */
    #[\Override]
    protected function getPaymentServiceAccessToken(string $scope, ?string $salesReference = null): mixed
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

    #[\Override]
    public function isCacheDeletedFromRemote(mixed $return): bool
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
