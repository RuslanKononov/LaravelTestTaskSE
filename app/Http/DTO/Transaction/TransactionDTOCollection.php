<?php

declare(strict_types=1);

namespace App\Http\DTO\Transaction;

use Illuminate\Support\Collection;
use Spatie\DataTransferObject\Attributes\CastWith;
use Spatie\DataTransferObject\Casters\ArrayCaster;

class TransactionDTOCollection
{
    public function __construct(
        #[CastWith(ArrayCaster::class, itemType: TransactionDTO::class)]
        public readonly array $transactionDTOs = [],
    ) {
    }

    public function countTransactions(): int
    {
        return count($this->transactionDTOs);
    }

    public function getTransactionUuidCollection(): Collection
    {
        return collect($this->transactionDTOs)->pluck('uuid');
    }
}
