<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Http\DTO\Subscription\SubscriptionTransactionDTOCollection;
use App\Http\DTO\Transaction\PreviousTransactionDTO;
use App\Http\DTO\Transaction\TransactionDTO;
use App\Http\DTO\Transaction\TransactionDTOCollection;
use App\Models\Transaction;
use App\Repositories\Factory\SubscriptionTransactionFactory;
use App\Repositories\Factory\TransactionFactory;
use Illuminate\Support\Facades\DB;

class TransactionRepository extends AbstractRepository
{
    public function __construct(
        private readonly TransactionFactory $transactionFactory,
        private readonly SubscriptionTransactionFactory $subscriptionTransactionFactory,
    ) {
    }

    public function beginTransaction(): void
    {
        DB::beginTransaction();
    }

    public function commitTransaction(): void
    {
        DB::commit();
    }

    public function rollbackTransaction(): void
    {
        DB::rollBack();
    }

    public function getPreviousTransactionByUserId(int $userId): PreviousTransactionDTO
    {
        $transaction = Transaction::where('user_id', $userId)->orderBy('uuid', 'desc')->first();

        return new PreviousTransactionDTO(
            userId: $userId,
            uuid: $transaction?->uuid,
            balance: $transaction?->balance ?? '0',
        );
    }

    public function persistTransaction(TransactionDTO $transactionDTO): TransactionDTO
    {
        $transaction = new Transaction();

        $transaction->uuid = $transactionDTO->uuid;
        $transaction->order_uuid = $transactionDTO->orderUuid;
        $transaction->user_id = $transactionDTO->userId;
        $transaction->transaction_type = $transactionDTO->transactionType->value;
        $transaction->amount = $transactionDTO->amount;
        $transaction->balance = $transactionDTO->balance;
        $transaction->previous_transaction_uuid = $transactionDTO->previousTransactionUuid;

        if (!$transaction->save()) {
            throw new \Exception('Transaction not saved');
        }

        return $transactionDTO;
    }

    public function persistTransactionCollection(
        TransactionDTOCollection $transactionDTOCollection
    ): TransactionDTOCollection {
        $transactionsData = $this->transactionFactory
            ->createTransactionsDataFromTransactionDTOCollection($transactionDTOCollection);

        $resultBulkInsert = Transaction::insert($transactionsData);
        if (!$resultBulkInsert) {
            // @todo log exception of Bulk transactions insert
            throw new \Exception('Bulk Transactions not saved');
        }

        $transactions = Transaction::whereIn('uuid', $transactionDTOCollection->getTransactionUuidCollection())->get();

        return $this->transactionFactory->createTransactionDTOFromCollection($transactions);
    }

    public function getTransactionCollectionByUserId(int $userId): TransactionDTOCollection
    {
        $transactions = Transaction::where('user_id', $userId)->orderBy('uuid', 'desc')->get();

        return $this->transactionFactory->createTransactionDTOFromCollection($transactions);
    }

    public function persistSubscriptionTransactionCollection(
        SubscriptionTransactionDTOCollection $subscriptionTransactionDTOCollection,
    ): SubscriptionTransactionDTOCollection {
        $transactionsData = $this->transactionFactory
            ->createTransactionsDataFromSubscriptionTransactionDTOCollection($subscriptionTransactionDTOCollection);

        $resultBulkInsert = Transaction::insert($transactionsData);
        if (!$resultBulkInsert) {
            // @todo log exception of Bulk transactions insert
            throw new \Exception('Subscription Transactions not saved');
        }

        $transactions = Transaction::whereIn(
            'uuid',
            $subscriptionTransactionDTOCollection->getSubscriptionTransactionUuidCollection()
        )->get();

        return $this->subscriptionTransactionFactory->createSubscriptionTransactionDTOFromCollection($transactions);
    }
}
