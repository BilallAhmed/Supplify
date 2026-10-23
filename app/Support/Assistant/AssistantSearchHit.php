<?php

namespace App\Support\Assistant;

use App\Enums\AssistantSourceType;

readonly class AssistantSearchHit
{
    public function __construct(
        public AssistantSourceType $type,
        public int $id,
        public string $title,
        public float $score,
        public string $matchKind,
        public ?string $url = null,
        public ?string $snippet = null,
    ) {}

    /**
     * @return array{
     *     type: string,
     *     id: int,
     *     title: string,
     *     score: float,
     *     match_kind: string,
     *     url: ?string,
     *     snippet: ?string
     * }
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'id' => $this->id,
            'title' => $this->title,
            'score' => $this->score,
            'match_kind' => $this->matchKind,
            'url' => $this->url,
            'snippet' => $this->snippet,
        ];
    }
}
