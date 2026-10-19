<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $orderCount = $user->orders()->count();
        $addressCount = $user->addresses()->count();
        $defaultAddress = $user->defaultAddress();

        $recentOrders = $user->orders()
            ->withCount('items')
            ->orderByDesc('placed_at')
            ->limit(5)
            ->get();

        return view('storefront.account.dashboard', compact(
            'user',
            'recentOrders',
            'orderCount',
            'addressCount',
            'defaultAddress',
        ));
    }
}
