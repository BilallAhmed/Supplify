<?php

namespace App\Services\Assistant;

use App\Enums\AssistantIntent;

class AssistantIntentRouter
{
    public function __construct(
        private readonly AssistantTextNormalizer $normalizer,
        private readonly AssistantAliasResolver $aliases,
        private readonly AssistantMedicalGuard $medical,
    ) {}

    public function route(string $query, bool $ingredientMode = false): AssistantIntent
    {
        $normalized = $this->normalizer->normalize($query);
        if ($this->medical->classify($query) !== null) {
            return AssistantIntent::Boundary;
        }
        $normalized = preg_replace('/^i am not pregnant[.!]\s*(?=what is .*price|how much .*cost)/u', '', $normalized);
        $tokens = $this->normalizer->tokens($normalized);

        if ($this->matchesAny($normalized, $tokens, config('assistant.search.intent_phrases.boundary', []))) {
            return AssistantIntent::Boundary;
        }

        $fromAlias = $this->aliases->primaryIntent($query);
        if ($fromAlias !== null) {
            return $fromAlias;
        }

        if ($this->matchesAny($normalized, $tokens, config('assistant.search.intent_phrases.delivery_returns_contact', []))) {
            return AssistantIntent::DeliveryReturnsContact;
        }

        if ($this->matchesAny($normalized, $tokens, config('assistant.search.intent_phrases.price_or_availability', []))) {
            return AssistantIntent::PriceOrAvailability;
        }

        if ($ingredientMode || $this->matchesAny($normalized, $tokens, config('assistant.search.intent_phrases.ingredients_or_directions', []))) {
            return AssistantIntent::IngredientsOrDirections;
        }

        if ($this->matchesAny($normalized, $tokens, config('assistant.search.intent_phrases.related_articles', []))) {
            return AssistantIntent::RelatedArticles;
        }

        if ($this->matchesAny($normalized, $tokens, config('assistant.search.intent_phrases.find_product', []))) {
            return AssistantIntent::FindProduct;
        }

        return AssistantIntent::FindProduct;
    }

    /**
     * @param  list<string>  $tokens
     * @param  list<string>  $phrases
     */
    private function matchesAny(string $normalized, array $tokens, array $phrases): bool
    {
        foreach ($phrases as $phrase) {
            $phrase = $this->normalizer->normalize($phrase);
            if ($phrase === '') {
                continue;
            }
            if ($this->normalizer->containsPhrase($normalized, $phrase)) {
                return true;
            }
            $phraseTokens = $this->normalizer->tokens($phrase);
            if ($phraseTokens !== [] && count(array_intersect($tokens, $phraseTokens)) === count($phraseTokens)) {
                return true;
            }
        }

        return false;
    }
}
