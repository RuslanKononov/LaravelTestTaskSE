<?php

declare(strict_types=1);

namespace App\Http\DTO\Order;

use Illuminate\Support\Collection;

class CreateBulkOrderDTO
{
    public function __construct(
        public readonly int $senderId,
        private array $createOrderDTOs = [],
        private string $totalAmount = '0',
    ) {
    }

    public function addCreateOrderDTO(CreateOrderDTO $createOrderDTO): void
    {
        $this->createOrderDTOs[] = $createOrderDTO;
    }

    public function getCreateOrderDTOs(): array
    {
        return $this->createOrderDTOs;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): void
    {
        $this->totalAmount = $totalAmount;
    }

    public function getOrderUuidCollection(): Collection
    {
        return collect($this->createOrderDTOs)->pluck('uuid');
    }
}
