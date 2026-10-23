<?php

namespace App\Services\Assistant;

use App\Contracts\AssistantSearchEngine;
use App\Enums\AssistantIntent;
use App\Enums\AssistantResponseType;
use App\Enums\AssistantSourceType;
use App\Models\AssistantDocument;
use App\Models\AssistantFaq;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Support\Assistant\AssistantChoice;
use App\Support\Assistant\AssistantSearchHit;
use App\Support\Assistant\AssistantSearchOutcome;
use Illuminate\Support\Str;

class AssistantKnowledgeSearch
{
    public function __construct(
        private readonly AssistantSearchEngine $engine,
        private readonly AssistantTextNormalizer $normalizer,
        private readonly AssistantAliasResolver $aliases,
        private readonly AssistantIntentRouter $intents,
        private readonly AssistantEligibility $eligibility,
        private readonly AssistantKnowledgeReader $reader,
    ) {}

    public function search(string $query): AssistantSearchOutcome
    {
        $query = trim(Str::limit($query, (int) config('assistant.limits.message_max_length', 500), ''));
        $normalized = $this->normalizer->normalize($query);
        $tokens = $this->normalizer->tokens($normalized);

        if (mb_strlen($normalized) < 2 || $tokens === []) {
            return new AssistantSearchOutcome(
                query: $query,
                intent: AssistantIntent::Unknown,
                confidence: 'none',
                suggestedType: AssistantResponseType::Unknown,
                message: 'Please enter at least two characters so Supplify Assistant can search the website.',
            );
        }

        $ingredientMode = $this->detectIngredientMode($normalized, $tokens);
        $intent = $this->intents->route($query, $ingredientMode);

        if ($intent === AssistantIntent::Boundary) {
            return new AssistantSearchOutcome(
                query: $query,
                intent: AssistantIntent::Boundary,
                confidence: 'high',
                suggestedType: AssistantResponseType::Boundary,
                message: 'Supplify Assistant can share published product information from this website, but cannot diagnose conditions, prescribe supplements, or decide medical suitability. Please speak with a qualified professional.',
            );
        }

        $resolvedAliases = $this->aliases->resolve($query);
        $aliasLabels = $resolvedAliases->pluck('phrase')->all();

        $limit = $this->limitFor($intent);
        $hits = [];

        foreach ($this->aliases->targetHits($query) as $target) {
            $hits[] = $this->hitFromTarget($target['type'], $target['id'], 95.0, 'alias', $target['alias']);
        }

        $hits = array_merge(
            $hits,
            $this->engine->search($normalized, $tokens, $limit * 3, $ingredientMode),
            $this->goalCategoryHits($normalized, $tokens),
            $this->policyAndFaqHits($intent, $normalized, $tokens),
        );

        if ($intent === AssistantIntent::FindProduct || $intent === AssistantIntent::IngredientsOrDirections) {
            foreach ($this->reader->liveProductFallback($query, $limit) as $product) {
                $hits[] = new AssistantSearchHit(
                    type: AssistantSourceType::Product,
                    id: (int) $product->id,
                    title: $product->name,
                    score: 70.0,
                    matchKind: 'live_fallback',
                    url: route('products.show', $product->slug, false),
                );
            }
        }

        $hits = $this->filterEligible($hits);
        $hits = $this->dedupeAndSort($hits);
        if ($intent === AssistantIntent::RelatedArticles) {
            $hits = array_values(array_filter($hits, fn ($hit) => $hit->type === AssistantSourceType::Post));
        }
        if ($intent === AssistantIntent::DeliveryReturnsContact) {
            $policies = array_values(array_filter($hits, fn ($hit) => $hit->matchKind === 'policy_page'));
            if ($policies !== []) {
                $hits = $policies;
            }
        }
        $hits = array_slice($hits, 0, $limit);

        if ($ingredientMode) {
            $hits = $this->applyIngredientPrecision($hits, $normalized, $tokens);
        }

        return $this->outcomeFromHits($query, $intent, $hits, $aliasLabels, $ingredientMode);
    }

    public function driverName(): string
    {
        return $this->engine->driverName();
    }

    /**
     * @param  list<string>  $tokens
     */
    private function detectIngredientMode(string $normalized, array $tokens): bool
    {
        if ($this->normalizer->containsPhrase($normalized, 'ingredient')
            || $this->normalizer->containsPhrase($normalized, 'supplement facts')
            || $this->normalizer->containsPhrase($normalized, 'directions')
            || $this->normalizer->containsPhrase($normalized, 'how to use')) {
            return true;
        }

        if (str_contains($normalized, 'vitamin')) {
            return true;
        }

        foreach (config('assistant.search.ingredient_cues', []) as $cue) {
            if ($this->normalizer->containsPhrase($normalized, (string) $cue)) {
                return true;
            }
        }

        return false;
    }

    private function limitFor(AssistantIntent $intent): int
    {
        return match ($intent) {
            AssistantIntent::RelatedArticles => (int) config('assistant.limits.max_articles_per_answer', 2),
            AssistantIntent::DeliveryReturnsContact => (int) config('assistant.limits.max_pages_per_answer', 2),
            default => (int) config('assistant.limits.max_products_per_answer', 3),
        };
    }

    /**
     * @param  list<string>  $tokens
     * @return list<AssistantSearchHit>
     */
    private function goalCategoryHits(string $normalized, array $tokens): array
    {
        $hits = [];
        $goals = config('assistant.search.wellness_goals', []);

        foreach ($goals as $goalKey => $phrases) {
            $matched = false;
            foreach ($phrases as $phrase) {
                if ($this->normalizer->containsPhrase($normalized, (string) $phrase)) {
                    $matched = true;
                    break;
                }
            }
            if (! $matched) {
                continue;
            }

            $categories = Category::query()
                ->where('is_active', true)
                ->where(function ($q) use ($goalKey, $phrases) {
                    $q->where('slug', 'like', '%'.$goalKey.'%');
                    foreach ($phrases as $phrase) {
                        $q->orWhere('name', 'like', '%'.$phrase.'%');
                    }
                })
                ->limit(5)
                ->get();

            foreach ($categories as $category) {
                if (! $this->eligibility->isCategoryEligible($category)) {
                    continue;
                }
                $hits[] = new AssistantSearchHit(
                    type: AssistantSourceType::Category,
                    id: (int) $category->id,
                    title: $category->name,
                    score: 78.0,
                    matchKind: 'goal_category',
                    url: route('shop.category', $category->slug, false),
                    snippet: 'Approved wellness-goal category mapping',
                );
            }
        }

        return $hits;
    }

    /**
     * @param  list<string>  $tokens
     * @return list<AssistantSearchHit>
     */
    private function policyAndFaqHits(AssistantIntent $intent, string $normalized, array $tokens): array
    {
        $hits = [];

        if ($intent === AssistantIntent::DeliveryReturnsContact
            || $this->normalizer->containsPhrase($normalized, 'shipping')
            || $this->normalizer->containsPhrase($normalized, 'return')
            || $this->normalizer->containsPhrase($normalized, 'contact')
            || $this->normalizer->containsPhrase($normalized, 'delivery')) {
            $slugs = [];
            if (preg_match('/shipping|delivery|deliver|ڈیلیوری/u', $normalized)) {
                $slugs[] = 'shipping';
            }
            if (preg_match('/return|refund|واپس/u', $normalized)) {
                $slugs[] = 'returns';
            }
            if (preg_match('/contact|support|help|رابطہ/u', $normalized)) {
                $slugs[] = 'contact';
            }
            if ($slugs === []) {
                $slugs = ['contact'];
            }
            foreach ($slugs as $slug) {
                $page = Page::query()->where('slug', $slug)->first();
                if ($page && $this->eligibility->isPageEligible($page)) {
                    $hits[] = new AssistantSearchHit(
                        type: AssistantSourceType::Page,
                        id: (int) $page->id,
                        title: $page->title,
                        score: 86.0,
                        matchKind: 'policy_page',
                        url: route('pages.show', $page->slug, false),
                    );
                }
            }
        }

        $faqs = AssistantFaq::query()->active()->limit(20)->get();
        foreach ($faqs as $faq) {
            if (! $this->eligibility->isFaqEligible($faq)) {
                continue;
            }
            $blob = $this->normalizer->normalize($faq->question.' '.$faq->answer.' '.implode(' ', $faq->aliases ?? []));
            if ($this->normalizer->containsPhrase($blob, $normalized)
                || $this->tokenCoverage($blob, $tokens) >= 0.7) {
                $hits[] = new AssistantSearchHit(
                    type: AssistantSourceType::Faq,
                    id: (int) $faq->id,
                    title: $faq->question,
                    score: 84.0,
                    matchKind: 'faq',
                    snippet: Str::limit(strip_tags((string) $faq->answer), 160),
                );
            }
        }

        return $hits;
    }

    /**
     * @param  list<AssistantSearchHit>  $hits
     * @param  list<string>  $tokens
     * @return list<AssistantSearchHit>
     */
    private function applyIngredientPrecision(array $hits, string $normalized, array $tokens): array
    {
        // Bare "vitamin" → clarify, do not return every vitamin product.
        if (count($tokens) === 1 && $tokens[0] === 'vitamin') {
            return [];
        }

        if (preg_match('/vitamin\s+([a-z]\s*-?\s*\d*|[a-z])/i', $normalized, $vitamin)) {
            $normalized = trim($vitamin[0]);
        } else {
            $normalized = trim(preg_replace('/\b(what|is|are|the|a|an|of|for|please|show|me|tell|about|price|cost|how|much|ingredients?|directions|use|stock|available|availability|in|does|contain)\b/u', '', $normalized) ?? $normalized);
            $normalized = preg_replace('/\s+/', ' ', $normalized);
        }
        if ($normalized === '') {
            return $hits;
        }

        return array_values(array_filter($hits, function (AssistantSearchHit $hit) use ($normalized) {
            if ($hit->type !== AssistantSourceType::Product) {
                return true;
            }

            $product = Product::query()->find($hit->id);
            if (! $product) {
                return false;
            }

            $fields = trim(($product->name ?? '').' '.($product->ingredients ?? '').' '.($product->supplement_facts ?? ''));

            // Require the full query phrase as whole tokens in name or ingredient fields.
            return $this->normalizer->containsPhrase($fields, $normalized);
        }));
    }

    /**
     * @param  list<AssistantSearchHit>  $hits
     * @return list<AssistantSearchHit>
     */
    private function filterEligible(array $hits): array
    {
        $out = [];

        foreach ($hits as $hit) {
            if (! $hit instanceof AssistantSearchHit) {
                continue;
            }

            $ok = match ($hit->type) {
                AssistantSourceType::Product => $this->eligibility->isProductEligible(Product::query()->with(['tags', 'categories'])->find($hit->id)),
                AssistantSourceType::Post => $this->eligibility->isPostEligible(Post::query()->find($hit->id)),
                AssistantSourceType::Page => $this->eligibility->isPageEligible(Page::query()->find($hit->id)),
                AssistantSourceType::Category => $this->eligibility->isCategoryEligible(Category::query()->find($hit->id)),
                AssistantSourceType::Faq => $this->eligibility->isFaqEligible(AssistantFaq::query()->find($hit->id)),
            };

            if (! $ok) {
                continue;
            }

            // Indexed docs must also pass live usable check when present.
            $document = AssistantDocument::query()
                ->where('source_type', $hit->type->value)
                ->where('source_id', $hit->id)
                ->active()
                ->first();

            if ($document && $this->reader->usableDocument($document, queueRefreshIfStale: true) === null) {
                continue;
            }

            $out[] = $hit;
        }

        return $out;
    }

    /**
     * @param  list<AssistantSearchHit>  $hits
     * @return list<AssistantSearchHit>
     */
    private function dedupeAndSort(array $hits): array
    {
        $best = [];

        foreach ($hits as $hit) {
            $key = $hit->type->value.':'.$hit->id;
            if (! isset($best[$key]) || $hit->score > $best[$key]->score) {
                $best[$key] = $hit;
            }
        }

        $list = array_values($best);
        usort($list, fn (AssistantSearchHit $a, AssistantSearchHit $b) => $b->score <=> $a->score);

        return $list;
    }

    /**
     * @param  list<AssistantSearchHit>  $hits
     * @param  list<string>  $aliasLabels
     */
    private function outcomeFromHits(
        string $query,
        AssistantIntent $intent,
        array $hits,
        array $aliasLabels,
        bool $ingredientMode,
    ): AssistantSearchOutcome {
        if ($hits === []) {
            return new AssistantSearchOutcome(
                query: $query,
                intent: $intent,
                confidence: 'none',
                suggestedType: AssistantResponseType::Unknown,
                message: 'No matching product, article, or page was found in Supplify’s approved website information. Try another name, or use Contact Us for help.',
                resolvedAliases: $aliasLabels,
                ingredientMode: $ingredientMode,
            );
        }

        $top = $hits[0]->score;
        $second = $hits[1]->score ?? 0;
        $high = (float) config('assistant.search.confidence.high', 80);
        $clarifyGap = (float) config('assistant.search.confidence.clarify_score_gap', 12);

        if (count($hits) === 1 && $top >= $high) {
            return new AssistantSearchOutcome(
                query: $query,
                intent: $intent,
                confidence: 'high',
                suggestedType: AssistantResponseType::Answer,
                message: 'Found a close match in approved website content.',
                hits: $hits,
                resolvedAliases: $aliasLabels,
                ingredientMode: $ingredientMode,
            );
        }

        if (count($hits) > 1 && ($top - $second) < $clarifyGap && $intent !== AssistantIntent::DeliveryReturnsContact) {
            $clarifications = [];
            foreach (array_slice($hits, 0, min(4, count($hits))) as $index => $hit) {
                $clarifications[] = new AssistantChoice(
                    id: $hit->type->value.':'.$hit->id,
                    label: $hit->title,
                    intent: $intent,
                    payload: (string) $hit->id,
                );
            }

            return new AssistantSearchOutcome(
                query: $query,
                intent: AssistantIntent::Clarify,
                confidence: 'medium',
                suggestedType: AssistantResponseType::Clarification,
                message: 'A few approved matches are close. Which one did you mean?',
                hits: $hits,
                clarifications: $clarifications,
                resolvedAliases: $aliasLabels,
                ingredientMode: $ingredientMode,
            );
        }

        if ($top >= $high) {
            return new AssistantSearchOutcome(
                query: $query,
                intent: $intent,
                confidence: 'high',
                suggestedType: AssistantResponseType::Answer,
                message: 'Here are the strongest approved matches.',
                hits: array_slice($hits, 0, (int) config('assistant.limits.max_products_per_answer', 3)),
                resolvedAliases: $aliasLabels,
                ingredientMode: $ingredientMode,
            );
        }

        if ($top >= (float) config('assistant.search.confidence.medium', 55)) {
            $clarifications = array_map(
                fn (AssistantSearchHit $hit) => new AssistantChoice(
                    id: $hit->type->value.':'.$hit->id,
                    label: $hit->title,
                    intent: $intent,
                    payload: (string) $hit->id,
                ),
                array_slice($hits, 0, 3),
            );

            return new AssistantSearchOutcome(
                query: $query,
                intent: AssistantIntent::Clarify,
                confidence: 'medium',
                suggestedType: AssistantResponseType::Clarification,
                message: 'Possible matches found. Confirm which option you want.',
                hits: $hits,
                clarifications: $clarifications,
                resolvedAliases: $aliasLabels,
                ingredientMode: $ingredientMode,
            );
        }

        return new AssistantSearchOutcome(
            query: $query,
            intent: $intent,
            confidence: 'low',
            suggestedType: AssistantResponseType::Unknown,
            message: 'No confident match was found in approved website information.',
            hits: [],
            resolvedAliases: $aliasLabels,
            ingredientMode: $ingredientMode,
        );
    }

    private function hitFromTarget(
        AssistantSourceType $type,
        int $id,
        float $score,
        string $kind,
        string $alias,
    ): AssistantSearchHit {
        $title = match ($type) {
            AssistantSourceType::Product => Product::query()->find($id)?->name,
            AssistantSourceType::Post => Post::query()->find($id)?->title,
            AssistantSourceType::Page => Page::query()->find($id)?->title,
            AssistantSourceType::Category => Category::query()->find($id)?->name,
            AssistantSourceType::Faq => AssistantFaq::query()->find($id)?->question,
        };

        return new AssistantSearchHit(
            type: $type,
            id: $id,
            title: $title ?: ($type->value.' #'.$id),
            score: $score,
            matchKind: $kind,
            snippet: 'Alias: '.$alias,
        );
    }

    /**
     * @param  list<string>  $tokens
     */
    private function tokenCoverage(string $haystack, array $tokens): float
    {
        if ($tokens === []) {
            return 0.0;
        }
        $hits = 0;
        foreach ($tokens as $token) {
            if ($this->normalizer->containsPhrase($haystack, $token)) {
                $hits++;
            }
        }

        return $hits / count($tokens);
    }
}
