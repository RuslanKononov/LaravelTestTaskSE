<?php

declare(strict_types=1);

namespace App\Http\DTO\Transaction;

use App\Http\Enum\Order\TransactionType;

class TransactionDTO
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $orderUuid,
        public readonly int $userId,
        public readonly TransactionType $transactionType,
        public readonly string $amount,
        public readonly string $balance,
        public readonly ?string $previousTransactionUuid,
    ) {
    }
}
