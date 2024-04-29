<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\DTO\Subscription\SubscriptionTransactionDTOCollection;
use Illuminate\Support\Collection;

interface SubscriptionServiceInterface
{
    public function paySubscription(
        Collection $userIdsWithoutActiveSubscription,
    ): SubscriptionTransactionDTOCollection;
}
