<?php

namespace App\Services\Assistant;

use App\Enums\AssistantSourceType;
use App\Models\AssistantDocument;
use App\Models\AssistantFaq;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Live eligibility gate for indexed knowledge. Pending index updates must not
 * expose unpublished/unapproved/deleted sources. Stale excerpts are omitted.
 */
class AssistantKnowledgeReader
{
    public function __construct(
        private readonly AssistantEligibility $eligibility,
        private readonly AssistantKnowledgeIndexer $indexer,
        private readonly AssistantKnowledgeSync $sync,
    ) {}

    /**
     * @return array{
     *     document: AssistantDocument,
     *     excerpts: list<string>,
     *     live_revision: string,
     *     index_stale: bool
     * }|null
     */
    public function usableDocument(AssistantDocument $document, bool $queueRefreshIfStale = true): ?array
    {
        if ($document->status !== AssistantDocument::STATUS_ACTIVE) {
            return null;
        }

        $source = $this->loadEligibleSource($document);
        if ($source === null) {
            return null;
        }

        $liveRevision = $this->indexer->revisionFor($document->source_type, $source);
        $stale = $document->content_revision !== $liveRevision;
        $excerpts = [];

        if (! $stale) {
            $excerpts = $document->relationLoaded('chunks')
                ? $document->chunks->pluck('content')->all()
                : $document->chunks()->orderBy('chunk_index')->pluck('content')->all();
        } elseif ($queueRefreshIfStale) {
            $this->sync->queueSource($document->source_type, $document->source_id);
        }

        return [
            'document' => $document,
            'excerpts' => array_values($excerpts),
            'live_revision' => $liveRevision,
            'index_stale' => $stale,
        ];
    }

    /**
     * Bounded live catalog path so newly eligible products are findable while indexing catches up.
     *
     * @return Collection<int, Product>
     */
    public function liveProductFallback(string $query, int $limit = 5): Collection
    {
        $limit = max(1, min($limit, (int) config('assistant.limits.max_products_per_answer', 3)));
        $term = trim($query);

        if (mb_strlen($term) < 2) {
            return collect();
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';

        return Product::query()
            ->published()
            ->with(['categories', 'tags'])
            ->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('ingredients', 'like', $like)
                    ->orWhereHas('categories', fn ($cq) => $cq->where('name', 'like', $like))
                    ->orWhereHas('tags', fn ($tq) => $tq->where('name', 'like', $like));
            })
            ->orderByDesc('is_featured')
            ->latest('published_at')
            ->limit($limit * 3)
            ->get()
            ->filter(fn (Product $product) => $this->eligibility->isProductEligible($product))
            ->take($limit)
            ->values();
    }

    private function loadEligibleSource(AssistantDocument $document): ?Model
    {
        return match ($document->source_type) {
            AssistantSourceType::Product => $this->eligibleProduct($document->source_id),
            AssistantSourceType::Post => $this->eligiblePost($document->source_id),
            AssistantSourceType::Page => $this->eligiblePage($document->source_id),
            AssistantSourceType::Category => $this->eligibleCategory($document->source_id),
            AssistantSourceType::Faq => $this->eligibleFaq($document->source_id),
        };
    }

    private function eligibleProduct(int $id): ?Product
    {
        $product = Product::query()->with(['categories', 'tags'])->find($id);

        return $product && $this->eligibility->isProductEligible($product) ? $product : null;
    }

    private function eligiblePost(int $id): ?Post
    {
        $post = Post::query()->with(['category', 'tags'])->find($id);

        return $post && $this->eligibility->isPostEligible($post) ? $post : null;
    }

    private function eligiblePage(int $id): ?Page
    {
        $page = Page::query()->find($id);

        return $page && $this->eligibility->isPageEligible($page) ? $page : null;
    }

    private function eligibleCategory(int $id): ?Category
    {
        $category = Category::query()->find($id);

        return $category && $this->eligibility->isCategoryEligible($category) ? $category : null;
    }

    private function eligibleFaq(int $id): ?AssistantFaq
    {
        $faq = AssistantFaq::query()->find($id);

        return $faq && $this->eligibility->isFaqEligible($faq) ? $faq : null;
    }
}
