<?php

declare(strict_types=1);

namespace App\Http\Controllers\Factory;

use App\Http\DTO\Order\CreateBulkOrderDTO;
use App\Http\DTO\Order\CreateOrderDTO;
use App\Http\Enum\Order\OrderType;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Symfony\Component\Uid\Uuid;

class OrderFactory
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    public function createFundsTransferOrderDTO(Request $request): CreateOrderDTO
    {
        return $this->createOrderDTO(
            orderType: OrderType::FundsTransfer,
            senderId: $request->user()->id,
            receiverId: $this->userRepository->getUserIdOrNullByEmail($request->get('receiver')),
            amount: sprintf("%.8f", $request->get('amount')),
            description: $request->get('description') ?? '',
        );
    }

    public function createBulkOrderDTO(Request $request): CreateBulkOrderDTO
    {
        $createBulkOrderDTO = new CreateBulkOrderDTO($request->user()->id);
        $totalAmount = '0';

        foreach ($request->bulkSend as $one) {
            $createOrderDTO = $this->createOrderDTO(
                orderType: OrderType::BulkTransfer,
                senderId: $createBulkOrderDTO->senderId,
                receiverId: $this->userRepository->getUserIdOrNullByEmail($one['receiver']),
                amount: sprintf("%.8f", $one['amount']),
                description: $one['description'] ?? '',
            );
            $createBulkOrderDTO->addCreateOrderDTO($createOrderDTO);
            $totalAmount = bcadd($totalAmount, $one['amount'], 8);
        }

        $createBulkOrderDTO->setTotalAmount($totalAmount);

        return $createBulkOrderDTO;
    }

    private function createOrderDTO(
        OrderType $orderType,
        int $senderId,
        int $receiverId,
        string $amount,
        string $description,
    ): CreateOrderDTO {
        return new CreateOrderDTO(
            uuid: Uuid::v6()->toRfc4122(),
            type: $orderType,
            senderId: $senderId,
            receiverId: $receiverId,
            initAmount: $amount,
            description: $description,
        );
    }
}
