<?php

namespace App\Support\Assistant;

use App\Enums\AssistantIntent;
use App\Enums\AssistantResponseType;
use InvalidArgumentException;

readonly class AssistantResponse
{
    /**
     * @param  list<AssistantSourceReference>  $sources
     * @param  list<AssistantProductCard>  $products
     * @param  list<AssistantContentCard>  $contents
     * @param  list<AssistantChoice>  $followUps
     * @param  list<AssistantChoice>  $clarifications
     */
    public function __construct(
        public AssistantResponseType $type,
        public AssistantIntent $intent,
        public string $answer,
        public array $sources = [],
        public array $products = [],
        public array $contents = [],
        public array $followUps = [],
        public array $clarifications = [],
    ) {
        foreach ($this->sources as $source) {
            if (! $source instanceof AssistantSourceReference) {
                throw new InvalidArgumentException('sources must contain AssistantSourceReference instances.');
            }
        }

        foreach ($this->products as $product) {
            if (! $product instanceof AssistantProductCard) {
                throw new InvalidArgumentException('products must contain AssistantProductCard instances.');
            }
        }

        foreach ($this->contents as $content) {
            if (! $content instanceof AssistantContentCard) {
                throw new InvalidArgumentException('contents must contain AssistantContentCard instances.');
            }
        }

        foreach ($this->followUps as $choice) {
            if (! $choice instanceof AssistantChoice) {
                throw new InvalidArgumentException('followUps must contain AssistantChoice instances.');
            }
        }

        foreach ($this->clarifications as $choice) {
            if (! $choice instanceof AssistantChoice) {
                throw new InvalidArgumentException('clarifications must contain AssistantChoice instances.');
            }
        }
    }

    /**
     * Structured payload for JSON / Alpine — never arbitrary HTML.
     *
     * @return array{
     *     type: string,
     *     intent: string,
     *     answer: string,
     *     sources: list<array<string, mixed>>,
     *     products: list<array<string, mixed>>,
     *     contents: list<array<string, mixed>>,
     *     follow_ups: list<array<string, mixed>>,
     *     clarifications: list<array<string, mixed>>
     * }
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'intent' => $this->intent->value,
            'answer' => $this->answer,
            'sources' => array_map(fn (AssistantSourceReference $s) => $s->toArray(), $this->sources),
            'products' => array_map(fn (AssistantProductCard $p) => $p->toArray(), $this->products),
            'contents' => array_map(fn (AssistantContentCard $c) => $c->toArray(), $this->contents),
            'follow_ups' => array_map(fn (AssistantChoice $c) => $c->toArray(), $this->followUps),
            'clarifications' => array_map(fn (AssistantChoice $c) => $c->toArray(), $this->clarifications),
        ];
    }

    public static function unknown(string $answer): self
    {
        return new self(
            type: AssistantResponseType::Unknown,
            intent: AssistantIntent::Unknown,
            answer: $answer,
        );
    }

    public static function boundary(string $answer): self
    {
        return new self(
            type: AssistantResponseType::Boundary,
            intent: AssistantIntent::Boundary,
            answer: $answer,
        );
    }
}
