<?php

namespace Tests\Unit\Assistant;

use App\Enums\AssistantIntent;
use App\Enums\AssistantResponseType;
use App\Enums\AssistantSourceType;
use App\Support\Assistant\AssistantChoice;
use App\Support\Assistant\AssistantContentCard;
use App\Support\Assistant\AssistantProductCard;
use App\Support\Assistant\AssistantResponse;
use App\Support\Assistant\AssistantSourceReference;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AssistantResponseContractTest extends TestCase
{
    public function test_response_serializes_to_structured_array_without_html(): void
    {
        $response = new AssistantResponse(
            type: AssistantResponseType::Answer,
            intent: AssistantIntent::FindProduct,
            answer: 'Magnesium Night matches your request.',
            sources: [
                new AssistantSourceReference(
                    type: AssistantSourceType::Product,
                    id: 12,
                    title: 'Magnesium Night',
                    url: 'https://example.test/products/magnesium-night',
                    revision: 'abc',
                ),
            ],
            products: [
                new AssistantProductCard(
                    id: 12,
                    name: 'Magnesium Night',
                    slug: 'magnesium-night',
                    url: 'https://example.test/products/magnesium-night',
                    price_formatted: 'Rs 2,500',
                    currency: 'PKR',
                    stock_label: 'In stock',
                    purchasable: true,
                    highlights: ['Magnesium glycinate'],
                ),
            ],
            contents: [
                new AssistantContentCard(
                    type: AssistantSourceType::Post,
                    id: 3,
                    title: 'Sleep routines',
                    url: 'https://example.test/blog/sleep-routines',
                    excerpt: 'Published guidance only.',
                ),
            ],
            followUps: [
                new AssistantChoice('price', 'What is its price?', AssistantIntent::PriceOrAvailability, '12'),
            ],
            clarifications: [
                new AssistantChoice('sleep', 'Sleep support', AssistantIntent::FindProduct),
            ],
        );

        $payload = $response->toArray();

        $this->assertSame('answer', $payload['type']);
        $this->assertSame('find_product', $payload['intent']);
        $this->assertSame('Magnesium Night matches your request.', $payload['answer']);
        $this->assertSame(12, $payload['products'][0]['id']);
        $this->assertSame('PKR', $payload['products'][0]['currency']);
        $this->assertSame('post', $payload['contents'][0]['type']);
        $this->assertSame('price', $payload['follow_ups'][0]['id']);
        $this->assertSame('sleep', $payload['clarifications'][0]['id']);
        $this->assertArrayNotHasKey('html', $payload);
    }

    public function test_unknown_and_boundary_helpers(): void
    {
        $unknown = AssistantResponse::unknown('No matching product was found.');
        $boundary = AssistantResponse::boundary('I cannot advise on medical suitability.');

        $this->assertSame(AssistantResponseType::Unknown, $unknown->type);
        $this->assertSame(AssistantIntent::Unknown, $unknown->intent);
        $this->assertSame(AssistantResponseType::Boundary, $boundary->type);
        $this->assertSame(AssistantIntent::Boundary, $boundary->intent);
    }

    public function test_invalid_card_collections_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AssistantResponse(
            type: AssistantResponseType::Answer,
            intent: AssistantIntent::FindProduct,
            answer: 'x',
            products: [['id' => 1]],
        );
    }

    public function test_supported_intents_are_documented(): void
    {
        $values = array_map(fn (AssistantIntent $i) => $i->value, AssistantIntent::cases());

        $this->assertContains('find_product', $values);
        $this->assertContains('price_or_availability', $values);
        $this->assertContains('ingredients_or_directions', $values);
        $this->assertContains('related_articles', $values);
        $this->assertContains('delivery_returns_contact', $values);
        $this->assertContains('clarify', $values);
        $this->assertContains('unknown', $values);
        $this->assertContains('boundary', $values);
    }
}
