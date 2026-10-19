<?php

use App\Http\Controllers\Storefront\BlogController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\ContactEnquiryController;
use App\Http\Controllers\Storefront\DesignSystemController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\NewsletterController;
use App\Http\Controllers\Storefront\PageController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\SearchController;
use App\Http\Controllers\Storefront\ShopController;
use App\Http\Controllers\Storefront\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/design-system', DesignSystemController::class)->name('design-system');

Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/shop/{category}', [ShopController::class, 'category'])->name('shop.category');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
Route::get('/products/{product}/quick-view', [ProductController::class, 'quickView'])
    ->name('products.quick-view');

Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
Route::post('/wishlist/{product}/toggle', [WishlistController::class, 'toggle'])
    ->middleware('throttle:60,1')
    ->name('wishlist.toggle');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/items', [CartController::class, 'store'])
    ->middleware('throttle:60,1')
    ->name('cart.items.store');
Route::patch('/cart/items/{item}', [CartController::class, 'update'])
    ->middleware('throttle:60,1')
    ->name('cart.items.update');
Route::delete('/cart/items/{item}', [CartController::class, 'destroy'])
    ->middleware('throttle:60,1')
    ->name('cart.items.destroy');
Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])
    ->middleware('throttle:30,1')
    ->name('cart.coupon.apply');
Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])
    ->middleware('throttle:30,1')
    ->name('cart.coupon.destroy');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout', [CheckoutController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('checkout.store');
Route::get('/checkout/confirmation/{order}', [CheckoutController::class, 'confirmation'])
    ->name('checkout.confirmation');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/feed', [BlogController::class, 'feed'])
    ->middleware('throttle:60,1')
    ->name('blog.feed');
Route::get('/blog/{post}', [BlogController::class, 'show'])->name('blog.show');

Route::get('/search', SearchController::class)
    ->middleware('throttle:45,1')
    ->name('search');

Route::get('/pages/{page}', [PageController::class, 'show'])->name('pages.show');
Route::post('/contact', [ContactEnquiryController::class, 'store'])
    ->middleware('throttle:8,1')
    ->name('contact.store');
Route::post('/newsletter', [NewsletterController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('newsletter.store');
