<?php

declare(strict_types=1);

namespace App\Http\Enum\Order;

enum TransactionType: string
{
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case Refund = 'refund';
    case Subscription = 'subscription';

    public function getSignModifier(): string
    {
        return match ($this) {
            self::Deposit, self::Refund => '+',
            self::Withdrawal, self::Subscription => '-',
        };
    }
}
