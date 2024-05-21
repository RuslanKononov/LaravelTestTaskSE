<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\Order\NotEnoughBalanceException;
use App\Exceptions\Order\UpdateOrderException;
use App\Http\Controllers\Factory\BulkOrderFactory;
use App\Http\Controllers\Factory\TransactionFactory;
use App\Http\Enum\Order\OrderState;
use App\Repositories\OrderRepository;
use App\Repositories\TransactionRepository;
use App\Services\BalanceServiceInterface;
use App\Services\TransactionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class BulkOrderController extends Controller
{
    public function __construct(
        private readonly BalanceServiceInterface $balanceService,
        private readonly BulkOrderFactory $bulkOrderFactory,
        private readonly OrderRepository $orderRepository,
        private readonly TransactionFactory $transactionFactory,
        private readonly TransactionRepository $transactionRepository,
        private readonly TransactionServiceInterface $transactionService,
    ) {
    }

    public function bulkSendFundsToUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'bulkSend' => ['required', 'array'],
            'bulkSend.*.receiver' => ['required', 'string','exists:App\Models\User,email', 'distinct'],
            'bulkSend.*.amount' => ['required', 'numeric', 'min:1'],
            'bulkSend.*.description' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $createBulkOrderDTO = $this->bulkOrderFactory->createBulkOrderDTO($request);

        $bulkOrderDTO = $this->orderRepository->createBulkOrder($createBulkOrderDTO);

        $this->transactionRepository->beginTransaction();
        try {
            $previousSenderTransactionDTO = $this->transactionRepository
                ->getPreviousTransactionByUserId($createBulkOrderDTO->senderId);
            $this->transactionService->getNewSenderBalanse(
                $previousSenderTransactionDTO,
                $createBulkOrderDTO->getTotalAmount(),
            );

            //  in this case we know last valid sender transactionUuid and if it'll duplicates - we'll get exception
            $transactionDTOs = [];
            foreach ($bulkOrderDTO->orderDTOs as $orderDTO) {
                $newSenderBalance = $this->transactionService->getNewSenderBalanse(
                    $previousSenderTransactionDTO,
                    $orderDTO->initAmount,
                );

                $withdrawalTransactionDTO = $this->transactionFactory->createWithdrawalTransactionDTO(
                    orderDTO: $orderDTO,
                    previousTransactionDTO: $previousSenderTransactionDTO,
                    balance: $newSenderBalance,
                );
                $transactionDTOs[] = $withdrawalTransactionDTO;

                // @todo make $previousReceiverTransactionDTOCollection and get data by receiverId from DTO
                //      $previousReceiverTransactionDTO = $previousReceiverTransactionDTOCollection
                //                                              ->getByReceiverId($receiverId);
                $previousReceiverTransactionDTO = $this->transactionRepository
                    ->getPreviousTransactionByUserId($orderDTO->receiverId);
                $newReceiverBalance = $this->transactionService->getNewReceiverBalanse(
                    $previousReceiverTransactionDTO,
                    $orderDTO->initAmount,
                );

                $transactionDTOs[] = $this->transactionFactory->createDepositTransactionDTO(
                    orderDTO: $orderDTO,
                    previousTransactionDTO: $previousReceiverTransactionDTO,
                    balance: $newReceiverBalance,
                );

                //set Data for next cycle
                $previousSenderTransactionDTO = $this->transactionFactory->createPreviousTransactionDTO(
                    userId: $orderDTO->senderId,
                    uuid: $withdrawalTransactionDTO->uuid,
                    balance: $newSenderBalance,
                );
            }

            $transactionDTOCollection = $this->transactionRepository->persistTransactionCollection(
                $this->transactionFactory->createTransactionDTOCollection($transactionDTOs)
            );

            $this->transactionRepository->commitTransaction();

            // Set Completed order state on success commit of transactions to storage
            $bulkOrderDTO = $this->orderRepository->bulkUpdateOrderState($bulkOrderDTO, OrderState::Completed);

            return response()->json(
                [
                    'message' => 'Order completed successfully',
                    'sender_id' => $createBulkOrderDTO->senderId,
                    'total_bulk_transactions_amount' => $createBulkOrderDTO->getTotalAmount(),
                    'bulk_orders' => $bulkOrderDTO->orderDTOs,
                    'transactions' => $transactionDTOCollection->transactionDTOs,
                ],
                Response::HTTP_OK
            );
        } catch (NotEnoughBalanceException) {
            $this->transactionRepository->rollbackTransaction();
            // @todo log exception
            $bulkOrderDTO = $this->orderRepository->bulkUpdateOrderState($bulkOrderDTO, OrderState::Failed);

            return response()->json(
                [
                    'message' => 'Bulk Order failed. Not enough balance',
                    'sender_id' => $createBulkOrderDTO->senderId,
                    'total_bulk_transactions_amount' => $createBulkOrderDTO->getTotalAmount(),
                    'balance' => $previousSenderTransactionDTO->balance,
                    'minimum_balance_limit' => $this->balanceService->getMinimumBalanceLimit(),
                    'bulk_orders' => $bulkOrderDTO->orderDTOs,
                ],
                Response::HTTP_PRECONDITION_FAILED
            );
        } catch (UpdateOrderException $exception) {
            // @todo log exception

            return response()->json(
                [
                    'message' => 'Bulk Update Order state failed, but Transactions was success.',
                    'sender_id' => $createBulkOrderDTO->senderId,
                    'total_bulk_transactions_amount' => $createBulkOrderDTO->getTotalAmount(),
                    'bulk_orders' => $bulkOrderDTO->orderDTOs,
                    'exception' => $exception->getMessage(),
                ],
                Response::HTTP_I_AM_A_TEAPOT
            );
        } catch (\Throwable $exception) {
            $this->transactionRepository->rollbackTransaction();
            // @todo log exception
            $bulkOrderDTO = $this->orderRepository->bulkUpdateOrderState($bulkOrderDTO, OrderState::Failed);

            return response()->json(
                [
                    'message' => 'Bulk Order failed',
                    'sender_id' => $createBulkOrderDTO->senderId,
                    'total_bulk_transactions_amount' => $createBulkOrderDTO->getTotalAmount(),
                    'bulk_orders' => $bulkOrderDTO->orderDTOs,
                    'exception' => $exception->getMessage(),
                ],
                Response::HTTP_BAD_REQUEST
            );
        }
    }
}
