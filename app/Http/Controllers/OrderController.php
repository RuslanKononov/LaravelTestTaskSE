<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\Order\NotEnoughBalanceException;
use App\Http\Controllers\Factory\OrderFactory;
use App\Http\Controllers\Factory\TransactionFactory;
use App\Http\Enum\Order\OrderState;
use App\Repositories\OrderRepository;
use App\Repositories\TransactionRepository;
use App\Services\BalanceServiceInterface;
use App\Services\TransactionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function __construct(
        private readonly BalanceServiceInterface $balanceService,
        private readonly OrderFactory $orderFactory,
        private readonly OrderRepository $orderRepository,
        private readonly TransactionFactory $transactionFactory,
        private readonly TransactionRepository $transactionRepository,
        private readonly TransactionServiceInterface $transactionService,
    ) {
    }

    public function sendFundsToUser(Request $request): JsonResponse
    {
        // Data validation
        $validator = Validator::make($request->all(), [
            'receiver' => ['required', 'email', 'exists:App\Models\User,email'],
            'amount' => ['required', 'numeric', 'min:1'],
            'description' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'success' => false,
                    'errors' => $validator->errors(),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // Creating Order to get ability of check user activity of order creaation.
        // If we should save only success orders - we'll move it inside transaction.
        $createOrderDTO = $this->orderFactory->createFundsTransferOrderDTO($request);
        $orderDTO = $this->orderRepository->createOrder($createOrderDTO);

        DB::beginTransaction();

        try {
            $previousSenderTransactionDTO = $this->transactionRepository
                ->getPreviousTransactionByUserId($orderDTO->senderId);
            $newSenderBalance = $this->transactionService->getNewSenderBalanse(
                $previousSenderTransactionDTO,
                $orderDTO->initAmount
            );

            $withdrawalTransactionDTO = $this->transactionFactory->createWithdrawalTransactionDTO(
                orderDTO: $orderDTO,
                previousTransactionDTO: $previousSenderTransactionDTO,
                balance: $newSenderBalance,
            );

            $previousReceiverTransactionDTO = $this->transactionRepository
                ->getPreviousTransactionByUserId($orderDTO->receiverId);
            $newReceiverBalance = $this->transactionService->getNewReceiverBalanse(
                $previousReceiverTransactionDTO,
                $orderDTO->initAmount,
            );

            $depositTransactionDTO = $this->transactionFactory->createDepositTransactionDTO(
                orderDTO: $orderDTO,
                previousTransactionDTO: $previousReceiverTransactionDTO,
                balance: $newReceiverBalance,
            );

            $this->transactionRepository->persistTransaction($withdrawalTransactionDTO);
            $this->transactionRepository->persistTransaction($depositTransactionDTO);

            DB::commit();

            // Set Completed order state on success commit of transactions to storage
            $orderDTO = $this->orderRepository->updateOrderState($orderDTO, OrderState::Completed);

            return response()->json(
                [
                    'message' => 'Order completed successfully',
                    'order_id' => $orderDTO->uuid,
                    'sender_id' => $orderDTO->senderId,
                    'receiver_id' => $orderDTO->receiverId,
                    'order_state' => $orderDTO->state->value,
                    'sender_balance' => $newSenderBalance,
                    'receiver_balance' => $newReceiverBalance,
                ],
                Response::HTTP_OK
            );
        } catch (NotEnoughBalanceException) {
            DB::rollBack();
            // Set Failed order state on fail commit of transactions to storage
            $orderDTO = $this->orderRepository->updateOrderState($orderDTO, OrderState::Failed);

            return response()->json(
                [
                    'message' => 'Order failed. Not enough balance',
                    'order_id' => $orderDTO->uuid,
                    'sender_id' => $orderDTO->senderId,
                    'receiver_id' => $orderDTO->receiverId,
                    'order_state' => $orderDTO->state->value,
                    'balance' => $previousSenderTransactionDTO->balance,
                ],
                Response::HTTP_PRECONDITION_FAILED
            );
        } catch (\Throwable $exception) {
            DB::rollBack();
            // @todo log exception
            // Set Failed order state on fail commit of transactions to storage
            $orderDTO = $this->orderRepository->updateOrderState($orderDTO, OrderState::Failed);

            return response()->json(
                [
                    'message' => 'Order failed',
                    'order_id' => $orderDTO->uuid,
                    'sender_id' => $orderDTO->senderId,
                    'receiver_id' => $orderDTO->receiverId,
                    'order_state' => $orderDTO->state->value,
                    'exception' => $exception->getMessage(),
                ],
                Response::HTTP_BAD_REQUEST
            );
        }
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

        $createBulkOrderDTO = $this->orderFactory->createBulkOrderDTO($request);

        $bulkOrderDTO = $this->orderRepository->createBulkOrder($createBulkOrderDTO);

        DB::beginTransaction();
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

            DB::commit();

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
            DB::rollBack();
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
        } catch (\Throwable $exception) {
            DB::rollBack();
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
