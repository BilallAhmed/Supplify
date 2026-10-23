<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\ContactEnquiry;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductActivity;
use App\Models\ProductVariation;
use App\Models\User;

class StoreOverviewService
{
    public function build(): array
    {
        $pending = Order::query()->where('status', 'pending')->count();
        $awaiting = Order::query()->whereIn('status', ['pending', 'paid', 'processing'])->where('fulfillment_status', '!=', 'fulfilled')->count();
        $unread = ContactEnquiry::query()->unread()->count();
        $low = Product::query()->where('stock_status', 'low_stock')->count();
        $out = Product::query()->where('stock_status', 'out_of_stock')->count();
        $units = (int) Product::query()->where('product_type', 'simple')->where('manage_stock', true)->sum('stock_quantity')
            + (int) ProductVariation::query()->where('is_enabled', true)->where('manage_stock', true)->whereHas('product', fn ($q) => $q->where('product_type', 'variable'))->sum('stock_quantity');

        return [
            'stats' => [
                ['label' => 'Total orders', 'value' => Order::query()->count(), 'hint' => 'All order statuses', 'url' => route('admin.orders.index'), 'icon' => 'orders'],
                ['label' => 'Products', 'value' => Product::query()->count(), 'hint' => 'Current catalog, including drafts', 'url' => route('admin.products.index'), 'icon' => 'products'],
                ['label' => 'Categories', 'value' => Category::query()->count(), 'hint' => 'Current product categories', 'url' => route('admin.categories.index'), 'icon' => 'categories'],
                ['label' => 'Inventory units', 'value' => $units, 'hint' => 'Stock-managed products and enabled variants', 'url' => route('admin.inventory.index'), 'icon' => 'inventory'],
                ['label' => 'Customers', 'value' => User::query()->where('role', UserRole::Customer)->count(), 'hint' => 'Registered customer accounts', 'url' => route('admin.customers.index'), 'icon' => 'customers'],
                ['label' => 'Pending orders', 'value' => $pending, 'hint' => 'Orders with pending status', 'url' => route('admin.orders.index', ['status' => 'pending']), 'icon' => 'orders'],
                ['label' => 'Awaiting fulfillment', 'value' => $awaiting, 'hint' => 'Open orders not fully fulfilled', 'url' => route('admin.orders.index'), 'icon' => 'inventory'],
                ['label' => 'Unread enquiries', 'value' => $unread, 'hint' => 'Contact messages awaiting review', 'url' => route('admin.contact-enquiries.index', ['status' => 'unread']), 'icon' => 'enquiries'],
            ],
            'alerts' => [
                ['count' => $pending, 'title' => 'Pending orders', 'description' => 'Review payment and order details before processing.', 'url' => route('admin.orders.index', ['status' => 'pending'])],
                ['count' => $low, 'title' => 'Low-stock products', 'description' => 'Check quantities and plan replenishment.', 'url' => route('admin.inventory.index', ['filter' => 'low'])],
                ['count' => $out, 'title' => 'Out-of-stock products', 'description' => 'Review availability before promoting these products.', 'url' => route('admin.inventory.index', ['filter' => 'out'])],
                ['count' => $unread, 'title' => 'Unread enquiries', 'description' => 'Open the inbox to review customer questions.', 'url' => route('admin.contact-enquiries.index', ['status' => 'unread'])],
            ],
            'orders' => Order::query()->orderByDesc('placed_at')->latest('id')->limit(6)->get(),
            'activity' => ProductActivity::query()->with('product')->latest('id')->limit(5)->get(),
            'quickLinks' => [
                ['label' => 'View orders', 'route' => 'admin.orders.index', 'icon' => 'orders'],
                ['label' => 'Add a product', 'route' => 'admin.products.create', 'icon' => 'products'],
                ['label' => 'Manage inventory', 'route' => 'admin.inventory.index', 'icon' => 'inventory'],
                ['label' => 'Edit homepage', 'route' => 'admin.homepage.edit', 'icon' => 'homepage'],
                ['label' => 'Read enquiries', 'route' => 'admin.contact-enquiries.index', 'icon' => 'enquiries'],
                ['label' => 'Upload media', 'route' => 'admin.media.index', 'icon' => 'media'],
                ['label' => 'View analytics', 'route' => 'admin.analytics', 'icon' => 'analytics'],
                ['label' => 'Store settings', 'route' => 'admin.settings.edit', 'icon' => 'settings'],
            ],
        ];
    }
}
