<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Http\DTO\Subscription\SubscriptionDTOCollection;
use App\Http\DTO\Subscription\SubscriptionTransactionDTOCollection;
use App\Models\Subscription;
use App\Repositories\Factory\SubscriptionFactory;
use Illuminate\Support\Collection;

class SubscriptionRepository
{
    public function __construct(
        private readonly SubscriptionFactory $subscriptionFactory,
    ) {
    }

    public function getUserIdsWithoutActiveSubscription(Collection $userIds): Collection
    {
        $expiredSubscriptions = Subscription::whereIn('user_id', $userIds)
            ->where('end_date', '<', now())
            ->pluck('user_id')->toArray();

        $usersWithoutSubscriptions = array_diff($userIds->toArray(), Subscription::pluck('user_id')->toArray());

        $result = collect(array_merge($expiredSubscriptions, $usersWithoutSubscriptions));

        return $result;
    }

    public function createSubscription(
        SubscriptionTransactionDTOCollection $subscriptionTransactionDTOCollection
    ): SubscriptionDTOCollection {
        $data = $this->subscriptionFactory->createDataFromDTOCollection($subscriptionTransactionDTOCollection);

        $result = Subscription::upsert(
            $data,
            ['user_id'],
            ['start_date', 'end_date']
        );

        if (!$result) {
            // @todo log exception of Subscription transactions insert
            throw new \Exception('Subscription not saved');
        }

        $subscriptions = Subscription::whereIn(
            'user_id',
            $subscriptionTransactionDTOCollection->getSubscriptionTransactionUserIdCollection(),
        )->get();

        return $this->subscriptionFactory->createSubscriptionDTOFromCollection($subscriptions);
    }
}
