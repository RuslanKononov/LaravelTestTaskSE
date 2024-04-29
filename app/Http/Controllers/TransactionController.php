<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\TransactionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionRepository $transactionRepository,
    ) {
    }

    public function transactionHistory(Request $request): JsonResponse
    {
        // get User in middleware
        $userId = $request->get('authenticatedUser')->id;

        $balance = $this->transactionRepository->getPreviousTransactionByUserId($userId)->balance;

        $transactionDTOCollection = $this->transactionRepository->getTransactionCollectionByUserId($userId);

        return response()->json(
            [
                'success' => true,
                'count_transactions' => $transactionDTOCollection->countTransactions(),
                'balance' => $balance,
                'transactions' => $transactionDTOCollection->transactionDTOs,
            ],
            Response::HTTP_OK,
        );
    }
}
