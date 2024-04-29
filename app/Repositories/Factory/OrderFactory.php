<?php

declare(strict_types=1);

namespace App\Repositories\Factory;

use App\Http\DTO\Order\CreateBulkOrderDTO;
use App\Http\DTO\Order\OrderDTO;
use App\Http\Enum\Order\OrderState;
use App\Http\Enum\Order\OrderType;
use App\Models\Order;

class OrderFactory
{
    public function createOrderDTOFromOrder(Order $order): OrderDTO
    {
        return new OrderDTO(
            uuid:         $order->uuid,
            type:         OrderType::from($order->order_type),
            senderId:     $order->sender_id,
            receiverId:   $order->receiver_id,
            initAmount:   $order->init_amount,
            description:  $order->description,
            state:        OrderState::from($order->order_state),
        );
    }

    public function createOrdersDataFromCreateBulkOrderDTO(CreateBulkOrderDTO $createBulkOrderDTO): array
    {
        $ordersData = [];
        foreach ($createBulkOrderDTO->getCreateOrderDTOs() as $createOrderDTO) {
            $ordersData[] = [
                'uuid' => $createOrderDTO->uuid,
                'order_type' => $createOrderDTO->type->value,
                'sender_id' => $createOrderDTO->senderId,
                'receiver_id' => $createOrderDTO->receiverId,
                'init_amount' => $createOrderDTO->initAmount,
                'description' => $createOrderDTO->description,
                'order_state' => OrderState::Created->value,
                'created_at' => now(),
            ];
        }

        return $ordersData;
    }
}
