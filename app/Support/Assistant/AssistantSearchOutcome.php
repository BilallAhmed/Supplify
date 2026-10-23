<?php

namespace App\Support\Assistant;

use App\Enums\AssistantIntent;
use App\Enums\AssistantResponseType;

readonly class AssistantSearchOutcome
{
    /**
     * @param  list<AssistantSearchHit>  $hits
     * @param  list<AssistantChoice>  $clarifications
     * @param  list<string>  $resolvedAliases
     */
    public function __construct(
        public string $query,
        public AssistantIntent $intent,
        public string $confidence,
        public AssistantResponseType $suggestedType,
        public string $message,
        public array $hits = [],
        public array $clarifications = [],
        public array $resolvedAliases = [],
        public bool $ingredientMode = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'query' => $this->query,
            'intent' => $this->intent->value,
            'confidence' => $this->confidence,
            'suggested_type' => $this->suggestedType->value,
            'message' => $this->message,
            'ingredient_mode' => $this->ingredientMode,
            'resolved_aliases' => $this->resolvedAliases,
            'hits' => array_map(fn (AssistantSearchHit $hit) => $hit->toArray(), $this->hits),
            'clarifications' => array_map(fn (AssistantChoice $c) => $c->toArray(), $this->clarifications),
        ];
    }
}
