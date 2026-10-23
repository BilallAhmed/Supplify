<?php

namespace App\Services\Assistant;

use App\Enums\AssistantSourceType;
use App\Models\AssistantFaq;
use App\Models\AssistantSourceApproval;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class AssistantEligibility
{
    public function isProductEligible(?Product $product): bool
    {
        if ($product === null || $product->trashed()) {
            return false;
        }

        if (! $product->is_active
            || $product->published_at === null
            || $product->published_at->isFuture()
            || ($product->catalog_visibility ?? 'visible') === 'hidden') {
            return false;
        }

        if ($product->hasEditorialDraft()) {
            return false;
        }

        return $this->isApproved(AssistantSourceType::Product, $product->id);
    }

    public function isPostEligible(?Post $post): bool
    {
        if ($post === null || $post->trashed()) {
            return false;
        }

        if (! $post->is_published
            || $post->published_at === null
            || $post->published_at->isFuture()) {
            return false;
        }

        return $this->isApproved(AssistantSourceType::Post, $post->id);
    }

    public function isPageEligible(?Page $page): bool
    {
        if ($page === null || $page->trashed()) {
            return false;
        }

        if (! $page->is_published
            || $page->published_at === null
            || $page->published_at->isFuture()) {
            return false;
        }

        return $this->isApproved(AssistantSourceType::Page, $page->id);
    }

    public function isCategoryEligible(?Category $category): bool
    {
        if ($category === null || $category->trashed()) {
            return false;
        }

        if (! $category->is_active) {
            return false;
        }

        return $this->isApproved(AssistantSourceType::Category, $category->id);
    }

    public function isFaqEligible(?AssistantFaq $faq): bool
    {
        if ($faq === null || $faq->trashed()) {
            return false;
        }

        return $faq->is_active;
    }

    public function isApproved(AssistantSourceType $type, int $sourceId): bool
    {
        return AssistantSourceApproval::query()
            ->where('source_type', $type->value)
            ->where('source_id', $sourceId)
            ->whereNotNull('approved_at')
            ->exists();
    }

    public function approve(Model $source, ?User $by = null): AssistantSourceApproval
    {
        $type = $this->sourceTypeFor($source);

        return AssistantSourceApproval::query()->updateOrCreate(
            [
                'source_type' => $type->value,
                'source_id' => $source->getKey(),
            ],
            [
                'approved_at' => now(),
                'approved_by' => $by?->id,
            ],
        );
    }

    public function withdraw(Model $source): void
    {
        $type = $this->sourceTypeFor($source);

        AssistantSourceApproval::query()
            ->where('source_type', $type->value)
            ->where('source_id', $source->getKey())
            ->delete();
    }

    /**
     * Fields allowed for a source type after eligibility passes.
     *
     * @return list<string>
     */
    public function readableFields(AssistantSourceType $type): array
    {
        $map = config('assistant.readable_fields', []);
        $fields = $map[$type->value] ?? [];

        return array_values(array_map('strval', $fields));
    }

    /**
     * @return list<string>
     */
    public function prohibitedFieldPatterns(): array
    {
        return array_values(array_map('strval', config('assistant.prohibited_fields', [])));
    }

    public function fieldIsReadable(AssistantSourceType $type, string $field): bool
    {
        $normalized = strtolower(trim($field));

        foreach ($this->prohibitedFieldPatterns() as $pattern) {
            $pattern = strtolower($pattern);
            if ($pattern === $normalized) {
                return false;
            }

            if (str_ends_with($pattern, '.*') && str_starts_with($normalized, substr($pattern, 0, -1))) {
                return false;
            }

            $prefixed = $type->value.'.'.$normalized;
            if ($pattern === $prefixed) {
                return false;
            }
        }

        return in_array($normalized, array_map('strtolower', $this->readableFields($type)), true)
            || in_array($normalized, array_map(
                fn (string $f) => strtolower(str_contains($f, '.') ? substr($f, strrpos($f, '.') + 1) : $f),
                $this->readableFields($type)
            ), true);
    }

    private function sourceTypeFor(Model $source): AssistantSourceType
    {
        return match (true) {
            $source instanceof Product => AssistantSourceType::Product,
            $source instanceof Post => AssistantSourceType::Post,
            $source instanceof Page => AssistantSourceType::Page,
            $source instanceof Category => AssistantSourceType::Category,
            $source instanceof AssistantFaq => AssistantSourceType::Faq,
            default => throw new InvalidArgumentException('Unsupported assistant source: '.$source::class),
        };
    }
}
