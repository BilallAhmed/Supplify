<?php

namespace App\Services\Assistant;

use App\Enums\AssistantIntent;
use App\Enums\AssistantResponseType;
use App\Models\Page;
use App\Models\Product;
use App\Support\Assistant\AssistantChoice;
use App\Support\Assistant\AssistantResponse;

/** Bounded store facts, with explicit gaps rather than inferred label claims. */
class AssistantFactAnswers
{
    public function __construct(
        private readonly AssistantQueryInterpreter $interpreter,
        private readonly AssistantKnowledgeSearch $search,
        private readonly AssistantResponseBuilder $builder,
        private readonly AssistantCatalogResolver $catalog,
        private readonly AssistantConversationState $state,
        private readonly AssistantSafetyRules $safety,
    ) {}

    public function respond(string $message): ?AssistantResponse
    {
        $text = mb_strtolower($message);
        if (preg_match('/\b(track\w*|order status|where is my order|where.s my order|discount code|coupon|cash on delivery|cod|payment methods?)\b/u', $text)) {
            $answer = preg_match('/\b(track\w*|order status|where is my order|where.s my order)\b/u', $text)
                ? 'I can’t access private order details or live tracking in this chat. Check your account or contact our team for order help. Please don’t share payment or sensitive information here.'
                : (preg_match('/\b(discount|coupon)\b/u', $text)
                    ? 'I don’t have a verified discount code to share. Check the store’s current offers or contact the team before ordering.'
                    : 'I can’t confirm that payment option from the website information available to this assistant. Check the payment options shown at checkout or ask our team before ordering.');

            return $this->support($answer);
        }
        if (preg_match('/\bship\b/u', $text)) {
            return $this->builder->fromSearch($this->search->search('shipping'));
        }
        if (preg_match('/\b(compare|cheaper|price comparison)\b/u', $text)) {
            $names = preg_replace('/\b(compare|which|is|cheaper|price|comparison|between)\b/u', '', $text);
            $parts = preg_split('/\b(?:and|or|versus|vs)\b/u', $names);
            if (count($parts) !== 2) {
                return $this->support('Please name two products to compare. I can compare their current prices and published label information, but cannot decide which is medically suitable for you.');
            }
            $products = array_map(fn ($part) => $this->resolve($part, false), $parts);
            if (! $products[0] || ! $products[1] || $products[0]->id === $products[1]->id) {
                return $this->support('I couldn’t confirm both products. Please use two product names from the current range so I can compare the listed facts.');
            }
            $cards = array_map(fn ($p) => $this->catalog->productCard($p->id), $products);
            if (in_array(null, $cards, true)) {
                return null;
            }
            $answer = implode(' ', array_map(fn ($c) => $c->name.': '.$c->price_formatted.'; '.$c->stock_label.'.', $cards));
            if (preg_match('/cheaper/u', $text)) {
                $a = $products[0]->effectivePriceCents();
                $b = $products[1]->effectivePriceCents();
                $answer .= $a === $b ? ' Their listed prices are equal.' : ' '.($a < $b ? $products[0]->name : $products[1]->name).' has the lower listed price.';
            }
            $answer .= ' This compares store facts only, not medical suitability. Which product would you like to explore?';

            return new AssistantResponse(type: AssistantResponseType::Answer, intent: AssistantIntent::FindProduct, answer: $answer,
                products: $cards, sources: array_map(fn ($p) => $this->catalog->productSource($p->id), $products),
                followUps: array_map(fn ($p) => new AssistantChoice('product:'.$p->id, $p->name, AssistantIntent::FindProduct, (string) $p->id), $products));
        }
        if (! preg_match('/\b(vegan|vegetarian|halal|gluten|benefits?)\b/u', $text, $attribute)) {
            return null;
        }
        $query = preg_replace('/\b(vegan|vegetarian|halal|gluten|free|benefits?|does|its|it|this|that|formula)\b/u', '', $text);
        $product = $this->resolve($query);
        if (! $product) {
            return $this->support('Which product would you like to check? Please share its product name so I can check the published information.');
        }
        if (str_starts_with($attribute[1], 'benefit')) {
            $benefits = $this->safety->sanitizeExcerpt($product->benefits, 500);
            $answer = $benefits !== '' ? 'Published benefits for '.$product->name.': '.$benefits.' These are website label statements, not advice about your personal health.' : 'I don’t have published benefit details for '.$product->name.' to quote. You can check its product page or ask the team for verified label information.';
        } else {
            // No reviewed dietary/certification field currently exists. Ingredients
            // alone cannot establish certification or absence of cross-contamination.
            $answer = 'I can’t verify '.str_replace('gluten', 'gluten-free', $attribute[1]).' status for '.$product->name.' from the published information available to me. Please confirm with the team or manufacturer; I won’t infer it from the product name or ingredient list.';
        }
        $card = $this->catalog->productCard($product->id);

        return new AssistantResponse(type: AssistantResponseType::Answer, intent: AssistantIntent::FindProduct, answer: $answer,
            products: $card ? [$card] : [], sources: array_values(array_filter([$this->catalog->productSource($product->id)])), followUps: $this->builder->productFollowUps($product->id));
    }

    private function resolve(string $query, bool $useContext = true): ?Product
    {
        $terms = $this->interpreter->terms($query);
        if ($terms === '') {
            $id = $useContext ? $this->state->selectedProductId() : null;

            return $id ? $this->catalog->freshProduct($id) : null;
        }
        $response = $this->builder->fromSearch($this->search->search($terms));

        return count($response->products) === 1 ? $this->catalog->freshProduct($response->products[0]->id) : null;
    }

    private function support(string $answer): AssistantResponse
    {
        $page = Page::query()->where('slug', 'contact')->first();
        $card = $page ? $this->catalog->pageCard($page->id) : null;

        return new AssistantResponse(type: AssistantResponseType::Answer, intent: AssistantIntent::DeliveryReturnsContact, answer: $answer,
            contents: $card ? [$card] : [], followUps: $this->builder->contactFollowUps());
    }
}
