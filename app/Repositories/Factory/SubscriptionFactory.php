<?php

declare(strict_types=1);

namespace App\Repositories\Factory;

use App\Http\DTO\Subscription\SubscriptionDTO;
use App\Http\DTO\Subscription\SubscriptionDTOCollection;
use App\Http\DTO\Subscription\SubscriptionTransactionDTO;
use App\Http\DTO\Subscription\SubscriptionTransactionDTOCollection;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SubscriptionFactory
{
    public function createDataFromDTOCollection(
        SubscriptionTransactionDTOCollection $subscriptionTransactionDTOCollection,
    ): array {
        $subscriptionData = [];
        /* @var SubscriptionTransactionDTO $transactionDTO */
        foreach ($subscriptionTransactionDTOCollection->subscriptionTransactionDTOs as $transactionDTO) {
            $subscriptionData[] = [
                'user_id' => $transactionDTO->userId,
                'start_date' => Carbon::now()->toDateTimeString(),
                'end_date' => Carbon::now()->addMonths(1)->toDateTimeString(),
            ];
        }

        return $subscriptionData;
    }

    public function createSubscriptionDTOFromCollection(Collection $subscriptions): SubscriptionDTOCollection
    {
        $subscriptionDTOs = [];
        foreach ($subscriptions as $subscription) {
            $subscriptionDTOs[] = $this->createSubscriptionDTOFromSubscription($subscription);
        }

        return new SubscriptionDTOCollection($subscriptionDTOs);
    }

    public function createSubscriptionDTOFromSubscription(Subscription $subscription): SubscriptionDTO
    {
        return new SubscriptionDTO(
            id: $subscription->id,
            userId: $subscription->user_id,
            startDate: $subscription->start_date,
            endDate: $subscription->end_date,
        );
    }
}
