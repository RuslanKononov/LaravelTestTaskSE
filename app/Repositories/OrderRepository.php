<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\Order\UpdateOrderException;
use App\Http\DTO\Order\BulkOrderDTO;
use App\Http\DTO\Order\CreateBulkOrderDTO;
use App\Http\DTO\Order\CreateOrderDTO;
use App\Http\DTO\Order\OrderDTO;
use App\Http\Enum\Order\OrderState;
use App\Models\Order;
use App\Repositories\Factory\OrderFactory;
use Illuminate\Support\Collection;

class OrderRepository
{
    public function __construct(
        private readonly OrderFactory $orderFactory,
    ) {
    }

    public function createOrder(CreateOrderDTO $createOrderDTO): OrderDTO
    {
        $order = new Order();

        $order->uuid = $createOrderDTO->uuid;
        $order->order_type = $createOrderDTO->type->value;
        $order->sender_id = $createOrderDTO->senderId;
        $order->receiver_id = $createOrderDTO->receiverId;
        $order->init_amount = $createOrderDTO->initAmount;
        $order->description = $createOrderDTO->description;
        $order->order_state = OrderState::Created->value;

        if (!$order->save()) {
            // @todo log exception of order insert
            throw new \Exception('Order not saved');
        }

        return $this->orderFactory->createOrderDTOFromOrder($order);
    }

    public function updateOrderState(OrderDTO $orderDTO, OrderState $orderState): OrderDTO
    {
        $order = Order::find($orderDTO->uuid);
        $order->order_state = $orderState->value;

        if (!$order->save()) {
            // @todo log exception of order state update
            throw new UpdateOrderException('Order state not updated');
        }

        return $this->orderFactory->createOrderDTOFromOrder($order);
    }

    public function createBulkOrder(CreateBulkOrderDTO $createBulkOrderDTO): BulkOrderDTO
    {
        $ordersData = $this->orderFactory->createOrdersDataFromCreateBulkOrderDTO($createBulkOrderDTO);

        $resultBulkInsert = Order::insert($ordersData);
        if (!$resultBulkInsert) {
            // @todo log exception of bulk orders insert
            throw new \Exception('Bulk Orders not saved');
        }

        return $this->getBulkOrderDTOByOrderUuidCollection($createBulkOrderDTO->getOrderUuidCollection());
    }

    public function bulkUpdateOrderState(BulkOrderDTO $bulkOrderDTO, OrderState $orderState): BulkOrderDTO
    {
        $updatedOrdersCount = Order::whereIn('uuid', $bulkOrderDTO->getOrderIds())
            ->update(['order_state' => $orderState->value]);
        if ($updatedOrdersCount !== $bulkOrderDTO->getOrderDTOsCount()) {
            // @todo log exception of bulk order state update
            throw new UpdateOrderException('Bulk order state not correctly updated');
        }

        return $this->getBulkOrderDTOByOrderUuidCollection($bulkOrderDTO->getOrderIds());
    }

    private function getBulkOrderDTOByOrderUuidCollection(Collection $uuidCollection): BulkOrderDTO
    {
        $updatedOrders = Order::whereIn('uuid', $uuidCollection)->get();
        $updatedOrderDTOs = [];
        foreach ($updatedOrders as $order) {
            $updatedOrderDTOs[] = $this->orderFactory->createOrderDTOFromOrder($order);
        }

        return new BulkOrderDTO($updatedOrderDTOs);
    }
}
