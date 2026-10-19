<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountOrderController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);

        $orders = $request->user()
            ->orders()
            ->withCount('items')
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->paginate(10);

        return view('storefront.account.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->loadMissing('items');

        return view('storefront.account.orders.show', compact('order'));
    }
}
