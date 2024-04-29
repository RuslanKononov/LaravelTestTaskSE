<?php

declare(strict_types=1);

namespace App\Http\DTO\Order;

use App\Http\Enum\Order\OrderType;

class CreateOrderDTO
{
    public function __construct(
        public readonly string $uuid,
        public readonly OrderType $type,
        public readonly int $senderId,
        public readonly int $receiverId,
        public readonly string $initAmount,
        public readonly string $description,
    ) {
    }
}
