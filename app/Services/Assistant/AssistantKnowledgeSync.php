<?php

namespace App\Services\Assistant;

use App\Enums\AssistantSourceType;
use App\Jobs\RefreshAssistantDocumentJob;
use App\Models\AssistantAlias;
use App\Models\AssistantFaq;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AssistantKnowledgeSync
{
    public function __construct(
        private readonly AssistantKnowledgeIndexer $indexer,
    ) {}

    public function queueSource(AssistantSourceType $type, int $sourceId): void
    {
        $dispatch = function () use ($type, $sourceId): void {
            $source = $this->resolve($type, $sourceId);
            $revision = $source ? $this->indexer->revisionFor($type, $source) : null;

            RefreshAssistantDocumentJob::dispatch(
                $type->value,
                $sourceId,
                $revision,
                now()->toIso8601String(),
            );
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($dispatch);
        } else {
            $dispatch();
        }
    }

    public function queueModel(Model $model): void
    {
        $type = match (true) {
            $model instanceof Product => AssistantSourceType::Product,
            $model instanceof Post => AssistantSourceType::Post,
            $model instanceof Page => AssistantSourceType::Page,
            $model instanceof Category => AssistantSourceType::Category,
            $model instanceof AssistantFaq => AssistantSourceType::Faq,
            $model instanceof ProductVariation => AssistantSourceType::Product,
            default => null,
        };

        if ($type === null) {
            return;
        }

        $id = $model instanceof ProductVariation ? (int) $model->product_id : (int) $model->getKey();
        $this->queueSource($type, $id);
    }

    public function queueProduct(int $productId): void
    {
        $this->queueSource(AssistantSourceType::Product, $productId);
    }

    public function queueProductsForCategory(Category $category): void
    {
        $this->queueSource(AssistantSourceType::Category, (int) $category->id);

        $category->products()->pluck('products.id')->each(function ($id): void {
            $this->queueProduct((int) $id);
        });
    }

    public function queueSourcesForTag(Tag $tag): void
    {
        $tag->products()->pluck('products.id')->each(fn ($id) => $this->queueProduct((int) $id));
        $tag->posts()->pluck('posts.id')->each(
            fn ($id) => $this->queueSource(AssistantSourceType::Post, (int) $id)
        );
    }

    public function queueAliasTouch(AssistantAlias $alias): void
    {
        if ($alias->target_type && $alias->target_id) {
            $this->queueSource($alias->target_type, (int) $alias->target_id);
        }
    }

    private function resolve(AssistantSourceType $type, int $id): ?Model
    {
        return match ($type) {
            AssistantSourceType::Product => Product::query()->withTrashed()->with(['categories', 'tags'])->find($id),
            AssistantSourceType::Post => Post::query()->withTrashed()->with(['category', 'tags'])->find($id),
            AssistantSourceType::Page => Page::query()->withTrashed()->find($id),
            AssistantSourceType::Category => Category::query()->withTrashed()->find($id),
            AssistantSourceType::Faq => AssistantFaq::query()->withTrashed()->find($id),
        };
    }
}
