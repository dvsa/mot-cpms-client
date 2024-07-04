<?php

namespace CpmsClientTest;

use CpmsClient\Authenticate\IdentityProviderInterface;
use CpmsClient\Authenticate\IdentityProviderTrait;

/**
 * Class MockUser
 *
 * @package CpmsClientTest
 */
class MockUser implements IdentityProviderInterface
{
    use IdentityProviderTrait;

    public function __construct()
    {
        $this->userId = 'MockUserId';
        $this->clientId = 'MockClientId';
        $this->clientSecret = 'MockClientSecret';
        $this->customerReference = 'MockCustomerReference';
        $this->costCentre = 'MockCostCentre';
        $this->version = 2;
    }
}
