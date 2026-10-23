<?php

namespace App\Services\Assistant;

use App\Enums\AssistantSourceType;
use App\Models\AssistantAlias;
use App\Models\AssistantDocument;
use App\Models\AssistantDocumentChunk;
use App\Models\AssistantFaq;
use App\Models\AssistantSyncState;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class AssistantKnowledgeIndexer
{
    public function __construct(
        private readonly AssistantEligibility $eligibility,
        private readonly AssistantTextNormalizer $normalizer,
    ) {}

    public function refresh(AssistantSourceType $type, int $sourceId): AssistantDocument
    {
        $source = $this->loadSource($type, $sourceId);

        if ($source === null || ! $this->isSourceIndexable($type, $source)) {
            return $this->markRemoved($type, $sourceId);
        }

        $payload = $this->buildPayload($type, $source);
        $revision = $payload['revision'];

        return DB::transaction(function () use ($type, $sourceId, $payload, $revision, $source) {
            $document = AssistantDocument::query()->firstOrNew([
                'source_type' => $type->value,
                'source_id' => $sourceId,
            ]);

            $document->fill([
                'title' => $payload['title'],
                'source_url' => $payload['url'],
                'searchable_text' => $payload['searchable_text'],
                'content_revision' => $revision,
                'is_approved' => true,
                'status' => AssistantDocument::STATUS_ACTIVE,
                'meta' => $payload['meta'],
                'source_updated_at' => $source->updated_at,
                'indexed_at' => now(),
            ]);
            $document->save();

            $document->chunks()->delete();

            foreach ($payload['chunks'] as $chunk) {
                $document->chunks()->create([
                    'chunk_index' => $chunk['chunk_index'],
                    'field_key' => $chunk['field_key'],
                    'content' => $chunk['content'],
                    'normalized_content' => $chunk['normalized_content'],
                ]);
            }

            return $document->fresh(['chunks']);
        });
    }

    /**
     * Apply index write only when this job is still authoritative for the live revision.
     */
    public function refreshIfCurrent(
        AssistantSourceType $type,
        int $sourceId,
        ?string $dispatchedRevision,
        ?string $dispatchedAtIso,
    ): ?AssistantDocument {
        $source = $this->loadSource($type, $sourceId);
        $liveRevision = $source ? $this->revisionFor($type, $source) : null;

        if ($dispatchedRevision !== null && $liveRevision !== null && $dispatchedRevision !== $liveRevision) {
            // Source changed after dispatch — index live state, do not use stale expectations.
            return $this->refresh($type, $sourceId);
        }

        $existing = AssistantDocument::query()
            ->where('source_type', $type->value)
            ->where('source_id', $sourceId)
            ->first();

        if ($existing
            && $dispatchedAtIso
            && $existing->indexed_at
            && $existing->indexed_at->gt($dispatchedAtIso)
            && $liveRevision !== null
            && $existing->content_revision === $liveRevision
            && $existing->status === AssistantDocument::STATUS_ACTIVE
        ) {
            return $existing;
        }

        return $this->refresh($type, $sourceId);
    }

    public function markRemoved(AssistantSourceType $type, int $sourceId): AssistantDocument
    {
        $document = AssistantDocument::query()->firstOrNew([
            'source_type' => $type->value,
            'source_id' => $sourceId,
        ]);

        $document->fill([
            'title' => $document->title ?: sprintf('%s #%d', $type->value, $sourceId),
            'source_url' => null,
            'searchable_text' => null,
            'content_revision' => 'removed:'.now()->timestamp,
            'is_approved' => false,
            'status' => AssistantDocument::STATUS_REMOVED,
            'meta' => null,
            'indexed_at' => now(),
        ]);
        $document->save();
        $document->chunks()->delete();

        return $document;
    }

    public function rebuildAll(): array
    {
        $counts = [
            'indexed' => 0,
            'removed' => 0,
            'errors' => 0,
        ];

        foreach (AssistantSourceType::cases() as $type) {
            if ($type === AssistantSourceType::Faq) {
                $ids = AssistantFaq::query()->withTrashed()->lazyById(100);
            } else {
                $ids = $this->sourceQuery($type)->lazyById(100);
            }

            foreach ($ids as $sourceRecord) {
                $id = $sourceRecord->id;
                try {
                    $doc = $this->refresh($type, (int) $id);
                    if ($doc->status === AssistantDocument::STATUS_ACTIVE) {
                        $counts['indexed']++;
                    } else {
                        $counts['removed']++;
                    }
                } catch (Throwable $e) {
                    $counts['errors']++;
                    report($e);
                }
            }
        }

        // Drop orphan active docs whose sources no longer exist.
        foreach (AssistantDocument::query()->active()->cursor() as $document) {
            if ($this->loadSource($document->source_type, $document->source_id) === null) {
                $this->markRemoved($document->source_type, $document->source_id);
                $counts['removed']++;
            }
        }

        $this->recordState('last_rebuild', [
            'finished_at' => now()->toIso8601String(),
            'counts' => $counts,
        ]);

        $this->recordState('last_successful_sync', [
            'finished_at' => now()->toIso8601String(),
            'mode' => 'rebuild',
        ]);

        return $counts;
    }

    public function reconcile(): array
    {
        $stats = [
            'refreshed' => 0,
            'removed' => 0,
            'unchanged' => 0,
            'scheduled_published' => 0,
        ];

        foreach (AssistantSourceType::cases() as $type) {
            foreach ($this->sourceQuery($type)->cursor() as $source) {
                $liveRevision = $this->revisionFor($type, $source);
                $document = AssistantDocument::query()
                    ->where('source_type', $type->value)
                    ->where('source_id', $source->id)
                    ->first();

                $shouldBeActive = $this->isSourceIndexable($type, $source);

                if ($shouldBeActive && (! $document || $document->status !== AssistantDocument::STATUS_ACTIVE || $document->content_revision !== $liveRevision)) {
                    $this->refresh($type, (int) $source->id);
                    $stats['refreshed']++;
                    if ($this->wasScheduledNowEligible($type, $source)) {
                        $stats['scheduled_published']++;
                    }
                } elseif (! $shouldBeActive && $document && $document->status === AssistantDocument::STATUS_ACTIVE) {
                    $this->markRemoved($type, (int) $source->id);
                    $stats['removed']++;
                } else {
                    $stats['unchanged']++;
                }
            }
        }

        $this->recordState('last_reconciliation', [
            'finished_at' => now()->toIso8601String(),
            'stats' => $stats,
        ]);
        $this->recordState('last_successful_sync', [
            'finished_at' => now()->toIso8601String(),
            'mode' => 'reconcile',
        ]);

        return $stats;
    }

    public function revisionFor(AssistantSourceType $type, Model $source): string
    {
        $payload = $this->buildPayload($type, $source);

        return $payload['revision'];
    }

    public function recordFailure(string $context, string $message): void
    {
        $this->recordState('last_failure', [
            'at' => now()->toIso8601String(),
            'context' => $context,
            'message' => Str::limit($message, 500, ''),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        return [
            'documents_active' => AssistantDocument::query()->active()->count(),
            'documents_removed' => AssistantDocument::query()->where('status', AssistantDocument::STATUS_REMOVED)->count(),
            'chunks' => AssistantDocumentChunk::query()->count(),
            'last_successful_sync' => $this->state('last_successful_sync'),
            'last_rebuild' => $this->state('last_rebuild'),
            'last_reconciliation' => $this->state('last_reconciliation'),
            'last_failure' => $this->state('last_failure'),
            'active_aliases' => AssistantAlias::query()->active()->count(),
            'pending_jobs' => DB::table('jobs')->where('payload', 'like', '%Assistant%')->count(),
            'failed_jobs' => DB::table('failed_jobs')->where('payload', 'like', '%Assistant%')->count(),
            'oldest_pending_at' => DB::table('jobs')->where('payload', 'like', '%Assistant%')->min('created_at'),
        ];
    }

    private function isSourceIndexable(AssistantSourceType $type, Model $source): bool
    {
        return match ($type) {
            AssistantSourceType::Product => $this->eligibility->isProductEligible($source),
            AssistantSourceType::Post => $this->eligibility->isPostEligible($source),
            AssistantSourceType::Page => $this->eligibility->isPageEligible($source),
            AssistantSourceType::Category => $this->eligibility->isCategoryEligible($source),
            AssistantSourceType::Faq => $this->eligibility->isFaqEligible($source),
        };
    }

    private function wasScheduledNowEligible(AssistantSourceType $type, Model $source): bool
    {
        if (! in_array($type, [AssistantSourceType::Product, AssistantSourceType::Post, AssistantSourceType::Page], true)) {
            return false;
        }

        $publishedAt = $source->published_at ?? null;

        return $publishedAt !== null
            && $publishedAt->lte(now())
            && $publishedAt->gte(now()->subHours(2));
    }

    /**
     * @return array{title: string, url: ?string, searchable_text: string, revision: string, meta: array<string, mixed>, chunks: list<array<string, mixed>>}
     */
    private function buildPayload(AssistantSourceType $type, Model $source): array
    {
        $max = (int) config('assistant.limits.excerpt_max_chars', 400);
        $fields = $this->extractFields($type, $source);
        $chunks = $this->normalizer->chunkFields($fields, $max);
        $searchable = $this->normalizer->normalize(implode("\n", array_column($chunks, 'content')));
        $title = $this->titleFor($type, $source);
        $url = $this->urlFor($type, $source);
        $meta = $this->metaFor($type, $source);

        $revisionSeed = [
            'type' => $type->value,
            'id' => $source->getKey(),
            'title' => $title,
            'fields' => $fields,
            'meta' => $meta,
            'updated_at' => optional($source->updated_at)?->toIso8601String(),
            'approved' => $this->isSourceIndexable($type, $source),
        ];

        return [
            'title' => $title,
            'url' => $url,
            'searchable_text' => $searchable,
            'revision' => hash('sha256', json_encode($revisionSeed, JSON_THROW_ON_ERROR)),
            'meta' => $meta,
            'chunks' => $chunks,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function extractFields(AssistantSourceType $type, Model $source): array
    {
        return match ($type) {
            AssistantSourceType::Product => array_filter([
                'name' => (string) $source->name,
                'short_description' => (string) $source->short_description,
                'ingredients' => (string) $source->ingredients,
                'supplement_facts' => (string) $source->supplement_facts,
                'directions' => (string) $source->directions,
                'warnings' => (string) $source->warnings,
                'categories' => $source->relationLoaded('categories')
                    ? $source->categories->pluck('name')->implode(', ')
                    : $source->categories()->pluck('name')->implode(', '),
                'tags' => $source->relationLoaded('tags')
                    ? $source->tags->reject(fn ($t) => in_array($t->slug, ['editorial-draft', 'temporary-pricing'], true))->pluck('name')->implode(', ')
                    : $source->tags()->whereNotIn('slug', ['editorial-draft', 'temporary-pricing'])->pluck('name')->implode(', '),
            ]),
            AssistantSourceType::Post => array_filter([
                'title' => (string) $source->title,
                'excerpt' => (string) $source->excerpt,
                'body' => (string) $source->body,
                'category' => (string) ($source->category?->name ?? $source->category()->value('name')),
            ]),
            AssistantSourceType::Page => array_filter([
                'title' => (string) $source->title,
                'body' => (string) $source->body,
                'sections' => is_array($source->sections) ? json_encode($source->sections) : '',
            ]),
            AssistantSourceType::Category => array_filter([
                'name' => (string) $source->name,
                'description' => (string) $source->description,
            ]),
            AssistantSourceType::Faq => array_filter([
                'question' => (string) $source->question,
                'answer' => (string) $source->answer,
                'aliases' => is_array($source->aliases) ? implode(', ', $source->aliases) : '',
            ]),
        };
    }

    private function titleFor(AssistantSourceType $type, Model $source): string
    {
        return match ($type) {
            AssistantSourceType::Product, AssistantSourceType::Category => (string) $source->name,
            AssistantSourceType::Post, AssistantSourceType::Page => (string) $source->title,
            AssistantSourceType::Faq => (string) $source->question,
        };
    }

    private function urlFor(AssistantSourceType $type, Model $source): ?string
    {
        try {
            return match ($type) {
                AssistantSourceType::Product => route('products.show', $source->slug, false),
                AssistantSourceType::Post => route('blog.show', $source->slug, false),
                AssistantSourceType::Page => route('pages.show', $source->slug, false),
                AssistantSourceType::Category => route('shop.category', $source->slug, false),
                AssistantSourceType::Faq => null,
            };
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function metaFor(AssistantSourceType $type, Model $source): array
    {
        return match ($type) {
            AssistantSourceType::Product => [
                'slug' => $source->slug,
                'sku' => $source->sku,
                'category_ids' => $source->categories()->pluck('categories.id')->all(),
                'tag_slugs' => $source->tags()->pluck('slug')->all(),
            ],
            AssistantSourceType::Post => [
                'slug' => $source->slug,
                'category_id' => $source->post_category_id,
            ],
            AssistantSourceType::Page => [
                'slug' => $source->slug,
            ],
            AssistantSourceType::Category => [
                'slug' => $source->slug,
            ],
            AssistantSourceType::Faq => [
                'intent' => $source->intent?->value,
            ],
        };
    }

    private function loadSource(AssistantSourceType $type, int $id): ?Model
    {
        return match ($type) {
            AssistantSourceType::Product => Product::query()->withTrashed()->with(['categories', 'tags'])->find($id),
            AssistantSourceType::Post => Post::query()->withTrashed()->with(['category', 'tags'])->find($id),
            AssistantSourceType::Page => Page::query()->withTrashed()->find($id),
            AssistantSourceType::Category => Category::query()->withTrashed()->find($id),
            AssistantSourceType::Faq => AssistantFaq::query()->withTrashed()->find($id),
        };
    }

    private function sourceQuery(AssistantSourceType $type)
    {
        return match ($type) {
            AssistantSourceType::Product => Product::query()->withTrashed(),
            AssistantSourceType::Post => Post::query()->withTrashed(),
            AssistantSourceType::Page => Page::query()->withTrashed(),
            AssistantSourceType::Category => Category::query()->withTrashed(),
            AssistantSourceType::Faq => AssistantFaq::query()->withTrashed(),
        };
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function recordState(string $key, array $value): void
    {
        AssistantSyncState::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function state(string $key): ?array
    {
        return AssistantSyncState::query()->where('key', $key)->value('value');
    }
}
