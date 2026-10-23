<?php

namespace App\Services\Assistant;

use App\Enums\AssistantSourceType;
use App\Enums\StockStatus;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Services\StoreSettings;
use App\Support\Assistant\AssistantContentCard;
use App\Support\Assistant\AssistantProductCard;
use App\Support\Assistant\AssistantSourceReference;
use Illuminate\Support\Str;

class AssistantCatalogResolver
{
    public function __construct(
        private readonly AssistantEligibility $eligibility,
        private readonly AssistantSafetyRules $safety,
        private readonly StoreSettings $store,
    ) {}

    public function productCard(int $productId): ?AssistantProductCard
    {
        $product = Product::query()->with(['categories', 'tags', 'images', 'variations'])->find($productId);
        if (! $product || ! $this->eligibility->isProductEligible($product)) {
            return null;
        }

        $status = $product->stock_status instanceof StockStatus
            ? $product->stock_status
            : StockStatus::tryFrom((string) $product->stock_status) ?? StockStatus::OutOfStock;

        $highlights = [];
        if (filled($product->short_description)) {
            $highlights[] = $this->safety->sanitizeExcerpt($product->short_description, 120);
        }

        return new AssistantProductCard(
            id: (int) $product->id,
            name: $product->name,
            slug: $product->slug,
            url: route('products.show', $product->slug),
            price_formatted: $product->effectivePriceFormatted(),
            currency: $this->store->currency(),
            stock_label: $status->label(),
            purchasable: $product->isPurchasable() && ! $product->hasTemporaryPricing(),
            image_url: $product->displayImageUrl(),
            highlights: $highlights,
        );
    }

    public function productSource(int $productId): ?AssistantSourceReference
    {
        $product = Product::query()->find($productId);
        if (! $product || ! $this->eligibility->isProductEligible($product)) {
            return null;
        }

        return new AssistantSourceReference(
            type: AssistantSourceType::Product,
            id: (int) $product->id,
            title: $product->name,
            url: route('products.show', $product->slug),
            revision: (string) optional($product->updated_at)?->timestamp,
        );
    }

    public function postCard(int $postId): ?AssistantContentCard
    {
        $post = Post::query()->with('category')->find($postId);
        if (! $post || ! $this->eligibility->isPostEligible($post)) {
            return null;
        }

        return new AssistantContentCard(
            type: AssistantSourceType::Post,
            id: (int) $post->id,
            title: $post->title,
            url: route('blog.show', $post->slug),
            excerpt: $this->safety->sanitizeExcerpt($post->excerpt, 160),
            meta: $post->category?->name,
            image_url: $post->featuredImageUrl(),
        );
    }

    public function pageCard(int $pageId): ?AssistantContentCard
    {
        $page = Page::query()->find($pageId);
        if (! $page || ! $this->eligibility->isPageEligible($page)) {
            return null;
        }

        return new AssistantContentCard(
            type: AssistantSourceType::Page,
            id: (int) $page->id,
            title: $page->title,
            url: route('pages.show', $page->slug),
            excerpt: $this->safety->sanitizeExcerpt(Str::limit(strip_tags((string) $page->body), 160), 160),
        );
    }

    public function freshProduct(int $productId): ?Product
    {
        $product = Product::query()->with(['categories', 'tags', 'variations'])->find($productId);

        return $product && $this->eligibility->isProductEligible($product) ? $product : null;
    }
}
