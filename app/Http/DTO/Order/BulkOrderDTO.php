<?php

declare(strict_types=1);

namespace App\Http\DTO\Order;

use Illuminate\Support\Collection;
use Spatie\DataTransferObject\Attributes\CastWith;
use Spatie\DataTransferObject\Casters\ArrayCaster;

class BulkOrderDTO
{
    public function __construct(
        #[CastWith(ArrayCaster::class, itemType: OrderDTO::class)]
        public readonly array $orderDTOs = [],
    ) {
    }

    public function getOrderIds(): Collection
    {
        return collect($this->orderDTOs)->pluck('uuid');
    }

    public function getOrderDTOsCount(): int
    {
        return count($this->orderDTOs);
    }
}
