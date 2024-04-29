<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Order\NotEnoughBalanceException;
use App\Http\DTO\Transaction\PreviousTransactionDTO;

class TransactionService implements TransactionServiceInterface
{
    public function __construct(
        private readonly BalanceServiceInterface $balanceService,
    ) {
    }

    /**
     * @throw NotEnoughBalanceException
     */
    public function getNewSenderBalanse(
        PreviousTransactionDTO $previousSenderTransactionDTO,
        string $amount,
    ): string {
        $newSenderBalance = bcsub($previousSenderTransactionDTO->balance, $amount, 8);
        if ($newSenderBalance < $this->balanceService->getMinimumBalanceLimit()) {
            throw new NotEnoughBalanceException();
        }

        return $newSenderBalance;
    }

    public function getNewReceiverBalanse(
        PreviousTransactionDTO $previousSenderTransactionDTO,
        string $amount
    ): string {
        return bcsub($previousSenderTransactionDTO->balance, $amount, 8);
    }
}
