<?php

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateOrderStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderUpdateRequest;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);

        $orders = Order::query()
            ->withCount('items')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('order_number', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('shipping_name', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'statuses' => OrderStatus::cases(),
        ]);
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->load(['items', 'statusHistories.user', 'user', 'coupon']);

        return view('admin.orders.show', [
            'order' => $order,
            'statuses' => OrderStatus::cases(),
            'paymentStatuses' => PaymentStatus::cases(),
            'fulfillmentStatuses' => FulfillmentStatus::cases(),
        ]);
    }

    public function update(OrderUpdateRequest $request, Order $order, UpdateOrderStatus $update): RedirectResponse
    {
        $this->authorize('update', $order);

        $update->execute($order, $request->validated(), $request->user());

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('flash_success', 'Order updated.');
    }
}
