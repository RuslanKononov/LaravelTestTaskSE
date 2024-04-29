<?php

declare(strict_types=1);

namespace App\Http\Enum\Order;

enum OrderType: string
{
    case FundsTransfer = 'funds_transfer';
    case BulkTransfer = 'bulk_transfer';
}
