<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\Order\NotEnoughBalanceException;
use App\Exceptions\Order\UpdateOrderException;
use App\Http\Controllers\Factory\OrderFactory;
use App\Http\Controllers\Factory\TransactionFactory;
use App\Http\Enum\Order\OrderState;
use App\Repositories\OrderRepository;
use App\Repositories\TransactionRepository;
use App\Services\TransactionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function __construct(
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

        $this->transactionRepository->beginTransaction();

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

            $this->transactionRepository->commitTransaction();

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
            $this->transactionRepository->rollbackTransaction();
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
        } catch (UpdateOrderException $exception) {
            // @todo log exception

            return response()->json(
                [
                    'message' => 'Update Order state failed, but Transactions was success.',
                    'order_id' => $orderDTO->uuid,
                    'sender_id' => $orderDTO->senderId,
                    'receiver_id' => $orderDTO->receiverId,
                    'order_state' => $orderDTO->state->value,
                    'exception' => $exception->getMessage(),
                ],
                Response::HTTP_I_AM_A_TEAPOT
            );
        } catch (\Throwable $exception) {
            $this->transactionRepository->rollbackTransaction();
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
}
