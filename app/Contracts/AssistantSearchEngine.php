<?php

namespace App\Contracts;

use App\Support\Assistant\AssistantSearchHit;

interface AssistantSearchEngine
{
    /**
     * Ranked document hits from the knowledge index (eligibility not yet applied).
     *
     * @param  list<string>  $tokens
     * @return list<AssistantSearchHit>
     */
    public function search(string $normalizedQuery, array $tokens, int $limit, bool $ingredientMode = false): array;

    public function driverName(): string;
}
