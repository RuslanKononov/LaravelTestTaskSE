<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Order\NotEnoughBalanceException;
use App\Http\DTO\Transaction\PreviousTransactionDTO;

interface TransactionServiceInterface
{
    /**
     * @throw NotEnoughBalanceException
     */
    public function getNewSenderBalanse(
        PreviousTransactionDTO $previousSenderTransactionDTO,
        string $amount,
    ): string;

    public function getNewReceiverBalanse(
        PreviousTransactionDTO $previousSenderTransactionDTO,
        string $amount,
    ): string;
}
