<?php

declare(strict_types=1);

namespace App\Http\DTO\Subscription;

use Spatie\DataTransferObject\Attributes\CastWith;
use Spatie\DataTransferObject\Casters\ArrayCaster;

class SubscriptionDTOCollection
{
    public function __construct(
        #[CastWith(ArrayCaster::class, itemType: SubscriptionDTO::class)]
        public readonly array $subscriptionDTOs = [],
    ) {
    }
}
