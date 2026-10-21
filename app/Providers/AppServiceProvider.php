<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\CustomerPolicy;
use App\Services\CartService;
use App\Services\TrackingScriptService;
use App\Services\WishlistService;
use Illuminate\Support\Facades\Gate;
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

        // Admin customer management uses User model with a dedicated policy.
        Gate::policy(User::class, CustomerPolicy::class);

        View::composer('storefront.partials.header', function ($view) {
            $view->with('cartCount', app(CartService::class)->itemCount());
        });

        View::composer('layouts.storefront', function ($view) {
            $view->with('wishlistIds', app(WishlistService::class)->ids());
            $view->with('tracking', app(TrackingScriptService::class)->forStorefront(request()));
        });
    }
}
