<?php

namespace App\Actions;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateOrderStatus
{
    /**
     * @param  array{status?: string, payment_status?: string, fulfillment_status?: string, note?: ?string}  $input
     */
    public function execute(Order $order, array $input, ?User $actor = null): Order
    {
        return DB::transaction(function () use ($order, $input, $actor) {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            $from = $order->status->value;
            $nextStatus = isset($input['status'])
                ? OrderStatus::from($input['status'])
                : $order->status;
            $nextPayment = isset($input['payment_status'])
                ? PaymentStatus::from($input['payment_status'])
                : $order->payment_status;
            $nextFulfillment = isset($input['fulfillment_status'])
                ? FulfillmentStatus::from($input['fulfillment_status'])
                : $order->fulfillment_status;

            $order->fill([
                'status' => $nextStatus,
                'payment_status' => $nextPayment,
                'fulfillment_status' => $nextFulfillment,
            ])->save();

            if ($from !== $nextStatus->value || filled($input['note'] ?? null)) {
                OrderStatusHistory::query()->create([
                    'order_id' => $order->id,
                    'from_status' => $from,
                    'to_status' => $nextStatus->value,
                    'user_id' => $actor?->id,
                    'note' => $input['note'] ?? 'Status updated by admin.',
                ]);
            }

            return $order->fresh(['items', 'statusHistories']);
        });
    }
}
