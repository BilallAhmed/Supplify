<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cod = 'cod';

    public function label(): string
    {
        return match ($this) {
            self::Cod => 'Cash on delivery',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Cod => 'Pay with cash when your order arrives. Card/online payment will be added once the gateway is connected.',
        };
    }
}
