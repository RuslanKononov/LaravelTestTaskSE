<?php

declare(strict_types=1);

namespace App\Repositories\Factory;

use App\Http\DTO\Subscription\SubscriptionTransactionDTO;
use App\Http\DTO\Subscription\SubscriptionTransactionDTOCollection;
use App\Http\Enum\Order\TransactionType;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;

class SubscriptionTransactionFactory
{
    public function createSubscriptionTransactionDTOFromCollection(
        Collection $transactions
    ): SubscriptionTransactionDTOCollection {
        $subscriptionTransactionDTOs = [];
        foreach ($transactions as $transaction) {
            $subscriptionTransactionDTOs[] = $this->createSubscriptionTransactionDTOFromTransaction($transaction);
        }
        return new SubscriptionTransactionDTOCollection($subscriptionTransactionDTOs);
    }

    public function createSubscriptionTransactionDTOFromTransaction(
        Transaction $transaction
    ): SubscriptionTransactionDTO {
        return new SubscriptionTransactionDTO(
            uuid: $transaction->uuid,
            userId: $transaction->user_id,
            transactionType: TransactionType::from($transaction->transaction_type),
            amount: $transaction->amount,
            balance: $transaction->balance,
            previousTransactionUuid: $transaction->previous_transaction_uuid,
        );
    }
}
