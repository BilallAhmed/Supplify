<?php

namespace App\Models;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'order_number',
    'user_id',
    'email',
    'phone',
    'shipping_name',
    'shipping_line1',
    'shipping_line2',
    'shipping_city',
    'shipping_state',
    'shipping_postal_code',
    'shipping_country',
    'shipping_phone',
    'billing_name',
    'billing_line1',
    'billing_line2',
    'billing_city',
    'billing_state',
    'billing_postal_code',
    'billing_country',
    'billing_phone',
    'status',
    'payment_status',
    'fulfillment_status',
    'payment_method',
    'subtotal_cents',
    'discount_cents',
    'shipping_cents',
    'tax_cents',
    'total_cents',
    'currency',
    'coupon_id',
    'coupon_code',
    'notes',
    'placed_at',
])]
class Order extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'fulfillment_status' => FulfillmentStatus::class,
            'payment_method' => PaymentMethod::class,
            'subtotal_cents' => 'integer',
            'discount_cents' => 'integer',
            'shipping_cents' => 'integer',
            'tax_cents' => 'integer',
            'total_cents' => 'integer',
            'placed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderByDesc('created_at');
    }

    public function totalFormatted(): string
    {
        return Money::format($this->total_cents, $this->currency);
    }

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }
}
