<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Config\Repository;

class BalanceService implements BalanceServiceInterface
{
    public function __construct(
        protected readonly Repository $config,
    ) {
    }

    public function getMinimumBalanceLimit(): string
    {
        return sprintf("%.8f", env('MINIMUM_BALANCE_LIMIT', '0'));
    }

    public function getSubscriptionFee(): string
    {
        return sprintf("%.8f", env('SUBSCRIPTION_FEE', '0'));
    }
}
