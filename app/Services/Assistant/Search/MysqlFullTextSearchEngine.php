<?php

namespace App\Services\Assistant\Search;

use App\Contracts\AssistantSearchEngine;
use App\Models\AssistantDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * MySQL FULLTEXT when indexes exist; otherwise delegates to the portable engine.
 */
class MysqlFullTextSearchEngine implements AssistantSearchEngine
{
    public function __construct(
        private readonly PortableTokenSearchEngine $portable,
    ) {}

    public function driverName(): string
    {
        return $this->fullTextAvailable() ? 'mysql_fulltext' : $this->portable->driverName();
    }

    public function search(string $normalizedQuery, array $tokens, int $limit, bool $ingredientMode = false): array
    {
        if (! $this->fullTextAvailable()) {
            return $this->portable->search($normalizedQuery, $tokens, $limit, $ingredientMode);
        }

        try {
            $boolean = $this->booleanQuery($tokens);
            if ($boolean === '') {
                return $this->portable->search($normalizedQuery, $tokens, $limit, $ingredientMode);
            }

            $rows = AssistantDocument::query()
                ->active()
                ->where('is_approved', true)
                ->whereRaw('MATCH(title, searchable_text) AGAINST (? IN BOOLEAN MODE)', [$boolean])
                ->select('*')
                ->selectRaw('MATCH(title, searchable_text) AGAINST (? IN BOOLEAN MODE) as ft_score', [$boolean])
                ->orderByDesc('ft_score')
                ->limit(max(1, min($limit, 25)))
                ->with('chunks')
                ->get();

            if ($rows->isEmpty()) {
                return $this->portable->search($normalizedQuery, $tokens, $limit, $ingredientMode);
            }

            // Exact titles are independent of the broad candidate limit.
            $exact = AssistantDocument::query()->active()->where('is_approved', true)
                ->whereRaw('LOWER(title) = ?', [$normalizedQuery])->with('chunks')->limit(10)->get();

            return $this->portable->rankDocuments($exact->merge($rows)->unique('id'), $normalizedQuery, $tokens, $limit, $ingredientMode);
        } catch (Throwable) {
            return $this->portable->search($normalizedQuery, $tokens, $limit, $ingredientMode);
        }
    }

    public function fullTextAvailable(): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return false;
        }

        if (! Schema::hasTable('assistant_documents')) {
            return false;
        }

        try {
            $indexes = DB::select("SHOW INDEX FROM assistant_documents WHERE Key_name = 'asst_docs_ft'");

            return $indexes !== [];
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param  list<string>  $tokens
     */
    private function booleanQuery(array $tokens): string
    {
        $parts = [];
        foreach ($tokens as $token) {
            if (mb_strlen($token) < 2) {
                continue;
            }
            $safe = preg_replace('/[^\\p{L}\\p{N}]+/u', '', $token) ?? '';
            if ($safe !== '') {
                $parts[] = '+'.$safe.'*';
            }
        }

        return implode(' ', $parts);
    }
}
