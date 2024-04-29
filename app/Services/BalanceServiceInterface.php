<?php

declare(strict_types=1);

namespace App\Services;

interface BalanceServiceInterface
{
    public function getMinimumBalanceLimit(): string;
    public function getSubscriptionFee(): string;
}
