<?php

namespace App\Providers;

use App\Services\CartService;
use App\Services\WishlistService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(function () {
            return Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        View::composer('storefront.partials.header', function ($view) {
            $view->with('cartCount', app(CartService::class)->itemCount());
        });

        View::composer('layouts.storefront', function ($view) {
            $view->with('wishlistIds', app(WishlistService::class)->ids());
        });
    }
}
