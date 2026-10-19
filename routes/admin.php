<?php

use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContactEnquiryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HomepageController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\ModulePlaceholderController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::post('categories/quick', [CategoryController::class, 'quickStore'])->name('categories.quick');
    Route::resource('categories', CategoryController::class)->except(['show']);

    Route::get('products/export', [ProductController::class, 'export'])->name('products.export');
    Route::post('products/import', [ProductController::class, 'import'])->name('products.import');
    Route::post('products/bulk', [ProductController::class, 'bulk'])->name('products.bulk');
    Route::post('products/{product}/duplicate', [ProductController::class, 'duplicate'])
        ->name('products.duplicate');
    Route::post('products/{product}/featured', [ProductController::class, 'toggleFeatured'])
        ->name('products.featured');
    Route::resource('products', ProductController::class)->except(['show']);

    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::put('inventory/{product}', [InventoryController::class, 'update'])->name('inventory.update');

    Route::get('homepage', [HomepageController::class, 'edit'])->name('homepage.edit');
    Route::put('homepage/{section}', [HomepageController::class, 'update'])->name('homepage.update');

    Route::resource('pages', PageController::class)->except(['show']);

    Route::get('contact-enquiries', [ContactEnquiryController::class, 'index'])->name('contact-enquiries.index');
    Route::get('contact-enquiries/{contactEnquiry}', [ContactEnquiryController::class, 'show'])->name('contact-enquiries.show');
    Route::delete('contact-enquiries/{contactEnquiry}', [ContactEnquiryController::class, 'destroy'])->name('contact-enquiries.destroy');

    Route::get('media', [MediaController::class, 'index'])->name('media.index');
    Route::get('media/resolve', [MediaController::class, 'resolve'])->name('media.resolve');
    Route::post('media/reindex', [MediaController::class, 'reindex'])->name('media.reindex');
    Route::post('media', [MediaController::class, 'store'])->name('media.store');
    Route::get('media/{medium}', [MediaController::class, 'show'])->name('media.show');
    Route::patch('media/{medium}', [MediaController::class, 'update'])->name('media.update');
    Route::delete('media/{medium}', [MediaController::class, 'destroy'])->name('media.destroy');

    Route::resource('blog', BlogController::class)
        ->parameters(['blog' => 'post'])
        ->except(['show']);

    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::put('orders/{order}', [OrderController::class, 'update'])->name('orders.update');

    Route::resource('coupons', CouponController::class)->except(['show']);

    $placeholders = [
        'customers.index' => ['customers', '/customers'],
        'settings.edit' => ['settings', '/settings'],
    ];

    foreach ($placeholders as $name => [$module, $path]) {
        Route::get($path, fn () => app(ModulePlaceholderController::class)($module))->name($name);
    }
});
