<?php

declare(strict_types=1);

namespace App\Http\Enum\Order;

enum OrderState: string
{
    case Created = 'created';
    case Failed = 'failed';
    case Completed = 'completed';
}
