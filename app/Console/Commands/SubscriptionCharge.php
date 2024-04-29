<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Repositories\SubscriptionRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Services\SubscriptionServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SubscriptionCharge extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:charge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for scheduled automatic debiting of funds from a user\'s account.';

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly SubscriptionServiceInterface $subscriptionService,
        private readonly TransactionRepository $transactionRepository,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {


        // We get all users but possible to change for activated or another rule
        $userIdsCollection = $this->userRepository->getAllActiveUserIds();

        $userIdsWithoutActiveSubscription = $this->subscriptionRepository
            ->getUserIdsWithoutActiveSubscription($userIdsCollection);

        $subscriptionTransactionDTOCollection = $this->subscriptionService
            ->paySubscription($userIdsWithoutActiveSubscription);

        DB::beginTransaction();
        try {
            $subscriptionTransactionDTOCollection = $this->transactionRepository
                ->persistSubscriptionTransactionCollection($subscriptionTransactionDTOCollection);

            $subscriptionDTOCollection = $this->subscriptionRepository
                ->createSubscription($subscriptionTransactionDTOCollection);

            // @todo show info from $subscriptionDTOCollection
            DB::commit();

            return self::SUCCESS;
        } catch (\Throwable) {
            DB::rollBack();

            return self::FAILURE;
        }
    }
}
