<?php

declare(strict_types=1);

namespace App\Http\DTO\Transaction;

class PreviousTransactionDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly ?string $uuid,
        public readonly string $balance,
    ) {
    }
}
