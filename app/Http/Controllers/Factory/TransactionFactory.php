<?php

declare(strict_types=1);

namespace App\Http\Controllers\Factory;

use App\Http\DTO\Order\OrderDTO;
use App\Http\DTO\Transaction\PreviousTransactionDTO;
use App\Http\DTO\Transaction\TransactionDTO;
use App\Http\DTO\Transaction\TransactionDTOCollection;
use App\Http\Enum\Order\TransactionType;
use Symfony\Component\Uid\Uuid;

class TransactionFactory
{
    public function createWithdrawalTransactionDTO(
        OrderDTO $orderDTO,
        PreviousTransactionDTO $previousTransactionDTO,
        string $balance,
    ): TransactionDTO {
        return $this->createTransactionDTO(
            orderDTO: $orderDTO,
            userId: $orderDTO->senderId,
            transactionType: TransactionType::Withdrawal,
            balance: $balance,
            previousTransactionUuid: $previousTransactionDTO->uuid,
        );
    }

    public function createDepositTransactionDTO(
        OrderDTO $orderDTO,
        PreviousTransactionDTO $previousTransactionDTO,
        string $balance,
    ): TransactionDTO {
        return $this->createTransactionDTO(
            orderDTO: $orderDTO,
            userId: $orderDTO->receiverId,
            transactionType: TransactionType::Deposit,
            balance: $balance,
            previousTransactionUuid: $previousTransactionDTO->uuid,
        );
    }

    private function createTransactionDTO(
        OrderDTO $orderDTO,
        int $userId,
        TransactionType $transactionType,
        string $balance,
        ?string $previousTransactionUuid,
    ): TransactionDTO {
        return new TransactionDTO(
            uuid: Uuid::v6()->toRfc4122(),
            orderUuid: $orderDTO->uuid,
            userId: $userId,
            transactionType: $transactionType,
            amount: sprintf('%s%s', $transactionType->getSignModifier(), $orderDTO->initAmount),
            balance: $balance,
            previousTransactionUuid: $previousTransactionUuid,
        );
    }

    public function createPreviousTransactionDTO(
        int $userId,
        string $uuid,
        string $balance
    ): PreviousTransactionDTO {
        return new PreviousTransactionDTO(
            userId: $userId,
            uuid: $uuid,
            balance: $balance,
        );
    }

    public function createTransactionDTOCollection(array $transactionDTOs): TransactionDTOCollection
    {
        return new TransactionDTOCollection($transactionDTOs);
    }
}
