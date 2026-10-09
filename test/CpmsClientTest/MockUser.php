<?php

declare(strict_types=1);

namespace CpmsClientTest;

use CpmsClient\Authenticate\IdentityProviderInterface;
use CpmsClient\Authenticate\IdentityProviderTrait;

class MockUser implements IdentityProviderInterface
{
    use IdentityProviderTrait;

    public function __construct()
    {
        $this->version = 2;
    }
}
