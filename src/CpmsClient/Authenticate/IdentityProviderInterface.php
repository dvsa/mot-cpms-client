<?php

declare(strict_types=1);

namespace CpmsClient\Authenticate;

interface IdentityProviderInterface
{
    public function getClientId(): string;

    public function getClientSecret(): string;

    public function getUserId(): string;

    public function getCustomerReference(): mixed;

    public function getCostCentre(): string;
}
