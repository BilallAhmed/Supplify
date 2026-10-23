<?php

namespace App\Providers;

use App\Contracts\AssistantSearchEngine;
use App\Models\AssistantAlias;
use App\Models\AssistantFaq;
use App\Models\AssistantSourceApproval;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Tag;
use App\Models\User;
use App\Observers\AssistantAliasObserver;
use App\Observers\AssistantCategoryObserver;
use App\Observers\AssistantFaqObserver;
use App\Observers\AssistantPageObserver;
use App\Observers\AssistantPostObserver;
use App\Observers\AssistantProductObserver;
use App\Observers\AssistantProductVariationObserver;
use App\Observers\AssistantSourceApprovalObserver;
use App\Observers\AssistantTagObserver;
use App\Policies\CustomerPolicy;
use App\Services\Assistant\AssistantSettings;
use App\Services\Assistant\Search\MysqlFullTextSearchEngine;
use App\Services\Assistant\Search\PortableTokenSearchEngine;
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
        $this->app->bind(AssistantSearchEngine::class, function ($app) {
            $driver = (string) config('assistant.search.driver', 'auto');

            return match ($driver) {
                'portable' => $app->make(PortableTokenSearchEngine::class),
                'fulltext', 'auto' => $app->make(MysqlFullTextSearchEngine::class),
                default => $app->make(PortableTokenSearchEngine::class),
            };
        });
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

        Product::observe(AssistantProductObserver::class);
        ProductVariation::observe(AssistantProductVariationObserver::class);
        Category::observe(AssistantCategoryObserver::class);
        Post::observe(AssistantPostObserver::class);
        Page::observe(AssistantPageObserver::class);
        Tag::observe(AssistantTagObserver::class);
        AssistantFaq::observe(AssistantFaqObserver::class);
        AssistantAlias::observe(AssistantAliasObserver::class);
        AssistantSourceApproval::observe(AssistantSourceApprovalObserver::class);

        View::composer('storefront.partials.header', function ($view) {
            $view->with('cartCount', app(CartService::class)->itemCount());
        });

        View::composer('layouts.storefront', function ($view) {
            $assistant = app(AssistantSettings::class);
            $view->with('wishlistIds', app(WishlistService::class)->ids());
            $view->with('tracking', app(TrackingScriptService::class)->forStorefront(request()));
            $view->with('assistantEnabled', $assistant->isEnabled());
            $view->with('assistantSettings', $assistant);
        });
    }
}
