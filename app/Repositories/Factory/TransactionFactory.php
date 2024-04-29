<?php

declare(strict_types=1);

namespace App\Repositories\Factory;

use App\Http\DTO\Subscription\SubscriptionTransactionDTOCollection;
use App\Http\DTO\Transaction\TransactionDTO;
use App\Http\DTO\Transaction\TransactionDTOCollection;
use App\Http\Enum\Order\TransactionType;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;

class TransactionFactory
{
    public function createTransactionDTOFromCollection(Collection $transactions): TransactionDTOCollection
    {
        $transactionDTOs = [];
        foreach ($transactions as $transaction) {
            $transactionDTOs[] = $this->createTransactionDTOFromTransaction($transaction);
        }
        return new TransactionDTOCollection($transactionDTOs);
    }

    public function createTransactionDTOFromTransaction(Transaction $transaction): TransactionDTO
    {
        return new TransactionDTO(
            uuid: $transaction->uuid,
            orderUuid: $transaction->order_uuid ?? 'null / subscription',
            userId: $transaction->user_id,
            transactionType: TransactionType::from($transaction->transaction_type),
            amount: $transaction->amount,
            balance: $transaction->balance,
            previousTransactionUuid: $transaction->previous_transaction_uuid,
        );
    }

    public function createTransactionsDataFromTransactionDTOCollection(
        TransactionDTOCollection $transactionDTOCollection
    ): array {
        $transactionsData = [];
        /* @var TransactionDTO $transactionDTO */
        foreach ($transactionDTOCollection->transactionDTOs as $transactionDTO) {
            $transactionsData[] = [
                'uuid' => $transactionDTO->uuid,
                'order_uuid' => $transactionDTO->orderUuid,
                'user_id' => $transactionDTO->userId,
                'transaction_type' => $transactionDTO->transactionType->value,
                'amount' => $transactionDTO->amount,
                'balance' => $transactionDTO->balance,
                'previous_transaction_uuid' => $transactionDTO->previousTransactionUuid,
                'created_at' => now(),
            ];
        }

        return $transactionsData;
    }

    public function createTransactionsDataFromSubscriptionTransactionDTOCollection(
        SubscriptionTransactionDTOCollection $transactionDTOCollection
    ): array {
        $transactionsData = [];
        /* @var TransactionDTO $transactionDTO */
        foreach ($transactionDTOCollection->subscriptionTransactionDTOs as $transactionDTO) {
            $transactionsData[] = [
                'uuid' => $transactionDTO->uuid,
                'order_uuid' => null,
                'user_id' => $transactionDTO->userId,
                'transaction_type' => $transactionDTO->transactionType->value,
                'amount' => $transactionDTO->amount,
                'balance' => $transactionDTO->balance,
                'previous_transaction_uuid' => $transactionDTO->previousTransactionUuid,
                'created_at' => now(),
            ];
        }

        return $transactionsData;
    }
}
