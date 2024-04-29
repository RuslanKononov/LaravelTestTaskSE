<?php

declare(strict_types=1);

namespace App\Services\Factory;

use App\Http\DTO\Subscription\SubscriptionTransactionDTO;
use App\Http\DTO\Subscription\SubscriptionTransactionDTOCollection;
use App\Http\DTO\Transaction\PreviousTransactionDTO;
use App\Http\Enum\Order\TransactionType;
use Symfony\Component\Uid\Uuid;

class SubscriptionFactory
{
    public function createSubscriptionTransactionDTO(
        PreviousTransactionDTO $previousTransactionDTO,
        string $fee,
        string $balance,
    ): SubscriptionTransactionDTO {
        return new SubscriptionTransactionDTO(
            uuid: Uuid::v6()->toRfc4122(),
            userId: $previousTransactionDTO->userId,
            transactionType: TransactionType::Subscription,
            amount: sprintf('%s%s', TransactionType::Subscription->getSignModifier(), $fee),
            balance: $balance,
            previousTransactionUuid: $previousTransactionDTO->uuid,
        );
    }

    public function createSubscriptionTransactionDTOCollection(
        array $subscriptionTransactionDTOs,
    ): SubscriptionTransactionDTOCollection {
        return new SubscriptionTransactionDTOCollection($subscriptionTransactionDTOs);
    }
}
