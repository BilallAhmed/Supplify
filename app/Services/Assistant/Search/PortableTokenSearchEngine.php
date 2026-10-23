<?php

namespace App\Services\Assistant\Search;

use App\Contracts\AssistantSearchEngine;
use App\Enums\AssistantSourceType;
use App\Models\AssistantDocument;
use App\Services\Assistant\AssistantTextNormalizer;
use App\Support\Assistant\AssistantSearchHit;

/**
 * Portable LIKE/token ranking — works on SQLite and MySQL without FULLTEXT.
 */
class PortableTokenSearchEngine implements AssistantSearchEngine
{
    public function __construct(
        private readonly AssistantTextNormalizer $normalizer,
    ) {}

    public function driverName(): string
    {
        return 'portable_token';
    }

    public function search(string $normalizedQuery, array $tokens, int $limit, bool $ingredientMode = false): array
    {
        if ($normalizedQuery === '' || $tokens === []) {
            return [];
        }

        $limit = max(1, min($limit, 25));
        $candidateLimit = min(80, $limit * 6);

        $documents = AssistantDocument::query()
            ->active()
            ->where('is_approved', true)
            ->with(['chunks' => fn ($q) => $q->orderBy('chunk_index')])
            ->where(function ($query) use ($normalizedQuery, $tokens) {
                $query->where('title', 'like', $this->like($normalizedQuery))
                    ->orWhere('searchable_text', 'like', $this->like($normalizedQuery));

                foreach ($tokens as $token) {
                    if (mb_strlen($token) < 2) {
                        continue;
                    }
                    $query->orWhere('title', 'like', $this->like($token))
                        ->orWhere('searchable_text', 'like', $this->like($token));
                }
            })
            ->limit($candidateLimit)
            ->get();

        $exact = AssistantDocument::query()->active()->where('is_approved', true)
            ->whereRaw('LOWER(title) = ?', [$normalizedQuery])->with('chunks')->limit(10)->get();
        $documents = $exact->merge($documents)->unique('id');

        return $this->rankDocuments($documents, $normalizedQuery, $tokens, $limit, $ingredientMode);
    }

    /** Use the same precision rules for portable and full-text candidate sets. */
    public function rankDocuments(iterable $documents, string $normalizedQuery, array $tokens, int $limit, bool $ingredientMode = false): array
    {
        $hits = [];

        foreach ($documents as $document) {
            $hit = $this->scoreDocument($document, $normalizedQuery, $tokens, $ingredientMode);
            if ($hit !== null) {
                $hits[] = $hit;
            }
        }

        usort($hits, fn (AssistantSearchHit $a, AssistantSearchHit $b) => $b->score <=> $a->score);

        return array_slice($hits, 0, $limit);
    }

    /**
     * @param  list<string>  $tokens
     */
    private function scoreDocument(
        AssistantDocument $document,
        string $normalizedQuery,
        array $tokens,
        bool $ingredientMode,
    ): ?AssistantSearchHit {
        $title = $this->normalizer->normalize($document->title);
        $searchable = $this->normalizer->normalize((string) $document->searchable_text);
        $type = $document->source_type instanceof AssistantSourceType
            ? $document->source_type
            : AssistantSourceType::from((string) $document->source_type);

        $score = 0.0;
        $matchKind = 'body';
        $snippet = null;

        if ($this->normalizer->isExactNormalized($title, $normalizedQuery)) {
            $score = 100.0;
            $matchKind = 'exact_name';
        } elseif ($this->normalizer->containsPhrase($title, $normalizedQuery)) {
            $score = 88.0;
            $matchKind = 'title_phrase';
        }

        $ingredientChunks = $document->chunks->where('field_key', 'ingredients')
            ->merge($document->chunks->where('field_key', 'supplement_facts'));

        if ($ingredientMode || $this->looksLikeIngredientQuery($normalizedQuery, $tokens)) {
            foreach ($ingredientChunks as $chunk) {
                if ($this->normalizer->containsPhrase($chunk->normalized_content, $normalizedQuery)
                    || $this->allTokensAsPhrase($chunk->normalized_content, $tokens)) {
                    $ingredientScore = 92.0;
                    if ($ingredientScore >= $score) {
                        $score = $ingredientScore;
                        $matchKind = 'exact_ingredient';
                        $snippet = $chunk->content;
                    }
                }
            }

            // Ingredient mode: do not accept weak body-only "vitamin" hits.
            if ($matchKind !== 'exact_ingredient' && $score < 88.0) {
                if ($this->isBareVitaminQuery($tokens) && ! $this->normalizer->containsPhrase($title, $normalizedQuery)) {
                    return null;
                }
            }
        }

        if ($score < 88.0) {
            $titleTokenHits = $this->tokenCoverage($title, $tokens);
            if ($titleTokenHits === 1.0) {
                $score = max($score, 75.0);
                $matchKind = $matchKind === 'body' ? 'title_tokens' : $matchKind;
            } elseif ($titleTokenHits >= 0.6) {
                $score = max($score, 55.0 + (20 * $titleTokenHits));
                $matchKind = $matchKind === 'body' ? 'title_partial' : $matchKind;
            }

            $bodyCoverage = $this->tokenCoverage($searchable, $tokens);
            if ($bodyCoverage === 1.0) {
                $score = max($score, 48.0);
            } elseif ($bodyCoverage >= 0.5) {
                $score = max($score, 30.0 + (15 * $bodyCoverage));
            }

            foreach ($document->chunks as $chunk) {
                if ($this->normalizer->containsPhrase($chunk->normalized_content, $normalizedQuery)) {
                    $fieldBoost = match ($chunk->field_key) {
                        'name', 'title', 'question' => 12.0,
                        'ingredients', 'supplement_facts' => $ingredientMode ? 0.0 : 8.0,
                        default => 4.0,
                    };
                    $score = max($score, 42.0 + $fieldBoost);
                    $snippet ??= $chunk->content;
                }
            }
        }

        if ($type === AssistantSourceType::Category && $score >= 40) {
            $score += 5;
            $matchKind = $matchKind === 'body' ? 'category' : $matchKind;
        }

        if ($score < (float) config('assistant.search.min_hit_score', 40)) {
            return null;
        }

        // Guard: bare "vitamin" must not promote Vitamin E for a D3 query (handled by phrase match),
        // and must not return low-specificity body hits in ingredient mode.
        if ($ingredientMode && $matchKind === 'body' && $score < 70) {
            return null;
        }

        return new AssistantSearchHit(
            type: $type,
            id: (int) $document->source_id,
            title: $document->title,
            score: round($score, 2),
            matchKind: $matchKind,
            url: $document->source_url,
            snippet: $snippet,
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

    /**
     * @param  list<string>  $tokens
     */
    private function allTokensAsPhrase(string $haystack, array $tokens): bool
    {
        return $this->normalizer->containsPhrase($haystack, implode(' ', $tokens));
    }

    /**
     * @param  list<string>  $tokens
     */
    private function looksLikeIngredientQuery(string $query, array $tokens): bool
    {
        if (str_contains($query, 'vitamin') || str_contains($query, 'mg') || str_contains($query, 'mcg') || str_contains($query, 'iu')) {
            return true;
        }

        return $this->isBareVitaminQuery($tokens);
    }

    /**
     * @param  list<string>  $tokens
     */
    private function isBareVitaminQuery(array $tokens): bool
    {
        return count($tokens) === 1 && $tokens[0] === 'vitamin';
    }

    private function like(string $term): string
    {
        return '%'.addcslashes($term, '%_\\').'%';
    }
}
