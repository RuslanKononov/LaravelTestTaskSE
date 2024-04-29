<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\DTO\Subscription\SubscriptionTransactionDTOCollection;
use App\Repositories\TransactionRepository;
use App\Services\Factory\SubscriptionFactory;
use Illuminate\Support\Collection;

class SubscriptionService implements SubscriptionServiceInterface
{
    public function __construct(
        private readonly TransactionServiceInterface $transactionService,
        private readonly TransactionRepository $transactionRepository,
        private readonly SubscriptionFactory $subscriptionFactory,
        private readonly BalanceServiceInterface $balanceService,
    ) {
    }

    public function paySubscription(
        Collection $userIdsWithoutActiveSubscription
    ): SubscriptionTransactionDTOCollection {
        $subscriptionTransactionDTOs = [];
        foreach ($userIdsWithoutActiveSubscription as $userId) {
            $previousSenderTransactionDTO = $this->transactionRepository->getPreviousTransactionByUserId($userId);

            $newSenderBalance = $this->transactionService->getNewSenderBalanse(
                $previousSenderTransactionDTO,
                $this->balanceService->getSubscriptionFee(),
            );

            $subscriptionTransactionDTOs[] = $this->subscriptionFactory->createSubscriptionTransactionDTO(
                $previousSenderTransactionDTO,
                $this->balanceService->getSubscriptionFee(),
                $newSenderBalance,
            );
        }

        return $this->subscriptionFactory->createSubscriptionTransactionDTOCollection($subscriptionTransactionDTOs);
    }
}
