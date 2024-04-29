<?php

declare(strict_types=1);

namespace App\Http\DTO\Subscription;

use Illuminate\Support\Collection;
use Spatie\DataTransferObject\Attributes\CastWith;
use Spatie\DataTransferObject\Casters\ArrayCaster;

class SubscriptionTransactionDTOCollection
{
    public function __construct(
        #[CastWith(ArrayCaster::class, itemType: SubscriptionTransactionDTO::class)]
        public readonly array $subscriptionTransactionDTOs = [],
    ) {
    }

    public function getSubscriptionTransactionUuidCollection(): Collection
    {
        return collect($this->subscriptionTransactionDTOs)->pluck('uuid');
    }

    public function getSubscriptionTransactionUserIdCollection(): Collection
    {
        return collect($this->subscriptionTransactionDTOs)->pluck('userId');
    }
}
