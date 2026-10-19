<?php

namespace App\Enums;

enum StockStatus: string
{
    case InStock = 'in_stock';
    case LowStock = 'low_stock';
    case OutOfStock = 'out_of_stock';

    public function label(): string
    {
        return match ($this) {
            self::InStock => 'In stock',
            self::LowStock => 'Low stock',
            self::OutOfStock => 'Out of stock',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::InStock => 'success',
            self::LowStock => 'warning',
            self::OutOfStock => 'danger',
        };
    }
}
