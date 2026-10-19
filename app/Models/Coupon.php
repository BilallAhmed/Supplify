<?php

namespace App\Models;

use App\Enums\CouponType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'code',
    'type',
    'value',
    'min_subtotal_cents',
    'max_uses',
    'used_count',
    'starts_at',
    'ends_at',
    'is_active',
])]
class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'value' => 'integer',
            'min_subtotal_cents' => 'integer',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public static function normalizeCode(string $code): string
    {
        return strtoupper(trim($code));
    }

    /**
     * Discount in cents for a cart subtotal. Never exceeds subtotal.
     * value is percent points (1–100) for Percent, or cents for Fixed.
     */
    public function discountCents(int $subtotalCents): int
    {
        if ($subtotalCents <= 0) {
            return 0;
        }

        $discount = match ($this->type) {
            CouponType::Percent => (int) floor($subtotalCents * min(100, max(0, $this->value)) / 100),
            CouponType::Fixed => max(0, $this->value),
        };

        return min($discount, $subtotalCents);
    }

    public function valueLabel(): string
    {
        return match ($this->type) {
            CouponType::Percent => $this->value.'%',
            CouponType::Fixed => \App\Support\Money::format($this->value),
        };
    }

    public function hasRemainingUses(): bool
    {
        if ($this->max_uses === null) {
            return true;
        }

        return $this->used_count < $this->max_uses;
    }

    public function isWithinSchedule(?\DateTimeInterface $at = null): bool
    {
        $at = \Illuminate\Support\Carbon::parse($at ?? now());

        if ($this->starts_at && $at->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $at->gt($this->ends_at)) {
            return false;
        }

        return true;
    }
}
