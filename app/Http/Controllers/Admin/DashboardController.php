<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            [
                'label' => 'Orders',
                'value' => '—',
                'hint' => 'Connected in Orders module',
            ],
            [
                'label' => 'Products',
                'value' => (string) Product::query()->count(),
                'hint' => 'Catalog SKUs (including drafts)',
            ],
            [
                'label' => 'Customers',
                'value' => (string) User::query()->where('role', UserRole::Customer)->count(),
                'hint' => 'Registered customer accounts',
            ],
            [
                'label' => 'Low stock',
                'value' => (string) Product::query()->where('stock_status', StockStatus::LowStock)->count(),
                'hint' => 'SKUs at or below threshold',
            ],
        ];

        return view('admin.dashboard', [
            'stats' => $stats,
            'recentActivity' => [
                [
                    'title' => 'Catalog module live',
                    'detail' => 'Products, categories, and inventory tools are available in admin.',
                    'when' => 'Just now',
                ],
                [
                    'title' => 'Next up: storefront',
                    'detail' => 'Shop listing and product detail pages land in Module 05.',
                    'when' => 'Upcoming',
                ],
            ],
        ]);
    }
}
