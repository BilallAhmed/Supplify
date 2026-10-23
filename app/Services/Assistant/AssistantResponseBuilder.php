<?php

namespace App\Services\Assistant;

use App\Enums\AssistantIntent;
use App\Enums\AssistantResponseType;
use App\Enums\AssistantSourceType;
use App\Models\AssistantFaq;
use App\Models\Page;
use App\Models\Product;
use App\Support\Assistant\AssistantChoice;
use App\Support\Assistant\AssistantContentCard;
use App\Support\Assistant\AssistantProductCard;
use App\Support\Assistant\AssistantResponse;
use App\Support\Assistant\AssistantSearchHit;
use App\Support\Assistant\AssistantSearchOutcome;
use App\Support\Assistant\AssistantSourceReference;

class AssistantResponseBuilder
{
    public function __construct(
        private readonly AssistantCatalogResolver $catalog,
        private readonly AssistantSafetyRules $safety,
        private readonly AssistantEligibility $eligibility,
    ) {}

    public function fromSearch(AssistantSearchOutcome $outcome): AssistantResponse
    {
        if ($outcome->suggestedType === AssistantResponseType::Boundary) {
            return $this->safety->boundaryResponse();
        }

        if ($outcome->suggestedType === AssistantResponseType::Clarification) {
            return $this->clarification($outcome);
        }

        if ($outcome->suggestedType === AssistantResponseType::Unknown || $outcome->hits === []) {
            return new AssistantResponse(
                type: AssistantResponseType::Unknown,
                intent: $outcome->intent,
                answer: $this->safety->sanitizeExcerpt($outcome->message, 500),
                followUps: $this->contactFollowUps(),
            );
        }

        return $this->answerFromHits($outcome);
    }

    public function priceFor(Product $product): AssistantResponse
    {
        $card = $this->catalog->productCard((int) $product->id);
        if (! $card) {
            return AssistantResponse::unknown('That product is no longer available in approved website listings.');
        }

        $answer = sprintf(
            '%s is currently listed at %s (%s). Availability: %s.',
            $card->name,
            $card->price_formatted,
            $card->currency,
            $card->stock_label,
        );

        return $this->productDetailResponse($product, $card, $answer, AssistantIntent::PriceOrAvailability);
    }

    public function stockFor(Product $product): AssistantResponse
    {
        $card = $this->catalog->productCard((int) $product->id);
        if (! $card) {
            return AssistantResponse::unknown('That product is no longer available in approved website listings.');
        }

        $answer = $card->purchasable
            ? sprintf('%s shows as %s on the website and can be added from the product page.', $card->name, $card->stock_label)
            : sprintf('%s currently shows as %s on the website.', $card->name, $card->stock_label);

        return $this->productDetailResponse($product, $card, $answer, AssistantIntent::PriceOrAvailability);
    }

    public function ingredientsFor(Product $product): AssistantResponse
    {
        $card = $this->catalog->productCard((int) $product->id);
        if (! $card) {
            return AssistantResponse::unknown('That product is no longer available in approved website listings.');
        }

        $ingredients = $this->safety->sanitizeExcerpt($product->ingredients, 400);
        $facts = $this->safety->sanitizeExcerpt($product->supplement_facts, 400);

        if ($ingredients === '' && $facts === '') {
            $answer = sprintf('No published ingredient list is available yet for %s on this website.', $card->name);
        } else {
            $parts = array_filter([
                $ingredients !== '' ? 'Ingredients (as published): '.$ingredients : null,
                $facts !== '' ? 'Supplement facts (as published): '.$facts : null,
            ]);
            $answer = implode(' ', $parts);
        }

        return $this->productDetailResponse($product, $card, $answer, AssistantIntent::IngredientsOrDirections);
    }

    public function directionsFor(Product $product): AssistantResponse
    {
        $card = $this->catalog->productCard((int) $product->id);
        if (! $card) {
            return AssistantResponse::unknown('That product is no longer available in approved website listings.');
        }

        $directions = $this->safety->sanitizeExcerpt($product->directions, 400);
        $warnings = $this->safety->sanitizeExcerpt($product->warnings, 240);

        if ($directions === '') {
            $answer = sprintf('No published label directions are listed yet for %s.', $card->name);
        } else {
            $answer = 'Published label directions (not personalized medical advice): '.$directions;
            if ($warnings !== '') {
                $answer .= ' Label warnings: '.$warnings;
            }
        }

        return $this->productDetailResponse($product, $card, $answer, AssistantIntent::IngredientsOrDirections);
    }

    /**
     * @param  list<AssistantContentCard>  $articles
     */
    public function relatedArticles(Product $product, array $articles): AssistantResponse
    {
        $card = $this->catalog->productCard((int) $product->id);
        if (! $card) {
            return AssistantResponse::unknown('That product is no longer available in approved website listings.');
        }

        if ($articles === []) {
            return new AssistantResponse(
                type: AssistantResponseType::Answer,
                intent: AssistantIntent::RelatedArticles,
                answer: sprintf('No related approved articles were found for %s right now. You can browse the blog for general wellness reading.', $card->name),
                products: [$card],
                sources: array_filter([$this->catalog->productSource((int) $product->id)]),
                followUps: $this->productFollowUps((int) $product->id),
            );
        }

        return new AssistantResponse(
            type: AssistantResponseType::Answer,
            intent: AssistantIntent::RelatedArticles,
            answer: sprintf('Here’s some reading to explore alongside %s.', $card->name),
            products: [$card],
            contents: $articles,
            sources: array_values(array_filter([
                $this->catalog->productSource((int) $product->id),
                ...array_map(fn (AssistantContentCard $c) => new AssistantSourceReference(
                    type: $c->type,
                    id: $c->id,
                    title: $c->title,
                    url: $c->url,
                ), $articles),
            ])),
            followUps: $this->productFollowUps((int) $product->id),
        );
    }

    public function whichProduct(array $productIds): AssistantResponse
    {
        $choices = [];
        $cards = [];
        foreach ($productIds as $id) {
            $card = $this->catalog->productCard((int) $id);
            if (! $card) {
                continue;
            }
            $cards[] = $card;
            $choices[] = new AssistantChoice(
                id: 'product:'.$card->id,
                label: $card->name,
                intent: AssistantIntent::FindProduct,
                payload: (string) $card->id,
            );
        }

        if ($choices === []) {
            return AssistantResponse::unknown('Those products are no longer available. Try searching again.');
        }

        return new AssistantResponse(
            type: AssistantResponseType::Clarification,
            intent: AssistantIntent::Clarify,
            answer: 'Which product would you like to explore? I can help with its price, availability, or label information.',
            products: $cards,
            clarifications: $choices,
        );
    }

    /**
     * @return list<AssistantChoice>
     */
    public function productFollowUps(int $productId): array
    {
        return [
            new AssistantChoice('price', 'What is its price?', AssistantIntent::PriceOrAvailability, (string) $productId),
            new AssistantChoice('stock', 'Is it in stock?', AssistantIntent::PriceOrAvailability, (string) $productId),
            new AssistantChoice('ingredients', 'Show ingredients', AssistantIntent::IngredientsOrDirections, (string) $productId),
            new AssistantChoice('directions', 'How do I use it?', AssistantIntent::IngredientsOrDirections, (string) $productId),
            new AssistantChoice('articles', 'Show related articles', AssistantIntent::RelatedArticles, (string) $productId),
        ];
    }

    /**
     * @return list<AssistantChoice>
     */
    public function contactFollowUps(): array
    {
        return [
            new AssistantChoice('contact', 'Contact Us', AssistantIntent::DeliveryReturnsContact, 'contact'),
        ];
    }

    private function clarification(AssistantSearchOutcome $outcome): AssistantResponse
    {
        [$products, $contents, $sources] = $this->hydrateHits($outcome->hits);

        return new AssistantResponse(
            type: AssistantResponseType::Clarification,
            intent: AssistantIntent::Clarify,
            answer: $this->safety->sanitizeExcerpt($outcome->message, 400),
            sources: $sources,
            products: $products,
            contents: $contents,
            clarifications: $outcome->clarifications !== []
                ? $outcome->clarifications
                : array_map(
                    fn (AssistantProductCard $card) => new AssistantChoice(
                        id: 'product:'.$card->id,
                        label: $card->name,
                        intent: AssistantIntent::FindProduct,
                        payload: (string) $card->id,
                    ),
                    $products,
                ),
        );
    }

    private function answerFromHits(AssistantSearchOutcome $outcome): AssistantResponse
    {
        [$products, $contents, $sources] = $this->hydrateHits($outcome->hits);
        $faqAnswers = $this->faqAnswers($outcome->hits);

        $answer = $this->safety->sanitizeExcerpt($outcome->message, 240);
        if ($faqAnswers !== []) {
            $answer = implode(' ', $faqAnswers);
        } elseif ($products !== [] && $outcome->intent === AssistantIntent::FindProduct) {
            $names = implode(', ', array_map(fn (AssistantProductCard $p) => $p->name, $products));
            $answer = 'Here’s what I found: '.$names.'. Would you like to check the price, stock, or label information?';
        } elseif ($contents !== [] && $products === []) {
            $answer = 'Here’s some reading that may help. Which guide would you like to explore?';
            $excerpts = [];
            foreach ($contents as $content) {
                if ($content->type === AssistantSourceType::Page) {
                    $page = Page::find($content->id);
                    if ($page && $this->eligibility->isPageEligible($page)) {
                        $excerpts[] = $page->title.': '.$this->safety->sanitizeExcerpt($page->body, 300);
                    }
                }
            }
            if ($excerpts !== []) {
                $answer = implode(' ', $excerpts);
            }
        }

        $followUps = $products !== []
            ? $this->productFollowUps($products[0]->id)
            : $this->contactFollowUps();

        return new AssistantResponse(
            type: AssistantResponseType::Answer,
            intent: $outcome->intent,
            answer: $answer,
            sources: $sources,
            products: $products,
            contents: $contents,
            followUps: $followUps,
        );
    }

    /**
     * @param  list<AssistantSearchHit>  $hits
     * @return array{0: list<AssistantProductCard>, 1: list<AssistantContentCard>, 2: list<AssistantSourceReference>}
     */
    private function hydrateHits(array $hits): array
    {
        $products = [];
        $contents = [];
        $sources = [];
        $maxProducts = (int) config('assistant.limits.max_products_per_answer', 3);
        $maxArticles = (int) config('assistant.limits.max_articles_per_answer', 2);
        $maxPages = (int) config('assistant.limits.max_pages_per_answer', 2);
        $articleCount = 0;
        $pageCount = 0;

        foreach ($hits as $hit) {
            if (! $hit instanceof AssistantSearchHit) {
                continue;
            }

            if ($hit->type === AssistantSourceType::Product && count($products) < $maxProducts) {
                $card = $this->catalog->productCard($hit->id);
                $source = $this->catalog->productSource($hit->id);
                if ($card && $source) {
                    $products[] = $card;
                    $sources[] = $source;
                }
            }

            if ($hit->type === AssistantSourceType::Post && $articleCount < $maxArticles) {
                $card = $this->catalog->postCard($hit->id);
                if ($card) {
                    $contents[] = $card;
                    $sources[] = new AssistantSourceReference($card->type, $card->id, $card->title, $card->url);
                    $articleCount++;
                }
            }

            if ($hit->type === AssistantSourceType::Page && $pageCount < $maxPages) {
                $card = $this->catalog->pageCard($hit->id);
                if ($card) {
                    $contents[] = $card;
                    $sources[] = new AssistantSourceReference($card->type, $card->id, $card->title, $card->url);
                    $pageCount++;
                }
            }
        }

        return [$products, $contents, $sources];
    }

    /**
     * @param  list<AssistantSearchHit>  $hits
     * @return list<string>
     */
    private function faqAnswers(array $hits): array
    {
        $out = [];
        foreach ($hits as $hit) {
            if ($hit->type !== AssistantSourceType::Faq) {
                continue;
            }
            $faq = AssistantFaq::query()->find($hit->id);
            if (! $faq || ! $this->eligibility->isFaqEligible($faq)) {
                continue;
            }
            $out[] = $this->safety->sanitizeExcerpt($faq->answer, 400);
        }

        return $out;
    }

    private function productDetailResponse(
        Product $product,
        AssistantProductCard $card,
        string $answer,
        AssistantIntent $intent,
    ): AssistantResponse {
        return new AssistantResponse(
            type: AssistantResponseType::Answer,
            intent: $intent,
            answer: $answer,
            sources: array_filter([$this->catalog->productSource((int) $product->id)]),
            products: [$card],
            followUps: $this->productFollowUps((int) $product->id),
        );
    }
}
