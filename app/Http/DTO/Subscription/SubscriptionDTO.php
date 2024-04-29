<?php

declare(strict_types=1);

namespace App\Http\DTO\Subscription;

class SubscriptionDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $startDate,
        public readonly string $endDate,
    ) {
    }
}
