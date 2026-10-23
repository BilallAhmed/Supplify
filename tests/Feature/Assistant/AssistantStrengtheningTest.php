<?php

namespace Tests\Feature\Assistant;

use App\Enums\AssistantResponseType;
use App\Enums\AssistantSourceType;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use App\Services\Assistant\AssistantConversationService;
use App\Services\Assistant\AssistantEligibility;
use App\Services\Assistant\AssistantKnowledgeIndexer;
use App\Services\StoreSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantStrengtheningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['assistant.enabled' => true, 'assistant.search.driver' => 'portable', 'cache.default' => 'array']);
        app(StoreSettings::class)->putMany(['assistant.enabled' => true]);
    }

    private function product(string $name, int $price = 280000): Product
    {
        $product = Product::factory()->create(['name' => $name, 'ingredients' => null, 'supplement_facts' => null, 'benefits' => null, 'price_cents' => $price, 'sale_price_cents' => null]);
        app(AssistantEligibility::class)->approve($product, User::factory()->admin()->create());
        app(AssistantKnowledgeIndexer::class)->refresh(AssistantSourceType::Product, $product->id);

        return $product;
    }

    public function test_medical_paraphrases_never_enter_product_flow(): void
    {
        $this->product('Vitamin E 400 IU');
        $this->product('Berberine 500mg');
        $chat = app(AssistantConversationService::class);
        foreach (['Vitamin E with warfarin?', 'I have diabetes. Recommend berberine.', 'Will berberine cure diabetes?', 'I am allergic to soy. Is Vitamin E suitable?', 'My 5 year old needs vitamin B50', 'Can I give it to my toddler?', 'mujhe diabetes hai berberine kitni leni chahiye', 'مجھے شوگر ہے بربرین لے سکتا ہوں؟', 'How many should I take?'] as $q) {
            $chat->reset();
            $reply = $chat->handle($q)['response'];
            $this->assertSame(AssistantResponseType::Boundary, $reply->type, $q);
            $this->assertSame([], $reply->products);
            $this->assertSame([], $reply->followUps);
        }
    }

    public function test_urgent_requests_have_distinct_response_and_no_shopping_actions(): void
    {
        $chat = app(AssistantConversationService::class);
        foreach (['I swallowed 30 tablets of magnesium', 'I have chest pain and difficulty breathing', 'I overdosed on vitamins'] as $q) {
            $reply = $chat->handle($q)['response'];
            $this->assertStringContainsString('urgent medical attention', $reply->answer);
            $this->assertSame([], $reply->products);
            $this->assertSame([], $reply->followUps);
        }
    }

    public function test_malformed_inputs_return_validation_not_server_error(): void
    {
        foreach ([[], ['message' => str_repeat('a', 501)], ['message' => ['CoQ10']], ['message' => (object) ['text' => 'hi']], ['choice_id' => ['price']], ['choice_payload' => ['1']], ['choice_id' => str_repeat('a', 121)]] as $payload) {
            $this->postJson(route('assistant.message'), $payload)->assertStatus(422);
        }
    }

    public function test_attributes_are_not_inferred_and_published_benefits_are_used(): void
    {
        $product = $this->product('Vitamin E 400 IU');
        $chat = app(AssistantConversationService::class);
        $reply = $chat->handle('Is Vitamin E vegan?')['response'];
        $this->assertStringContainsString('can’t verify vegan status', $reply->answer);
        $this->assertSame($product->id, $reply->products[0]->id);
        $reply = $chat->handle('What are its benefits?')['response'];
        $this->assertStringContainsString('don’t have published benefit', $reply->answer);
        $product->update(['benefits' => 'Convenient softgel format.']);
        $reply = $chat->handle('What are its benefits?')['response'];
        $this->assertStringContainsString('Convenient softgel format.', $reply->answer);
    }

    public function test_comparison_uses_live_prices_and_selectable_products(): void
    {
        $a = $this->product('CoQ10 200mg', 280000);
        $b = $this->product('NADH 20mg', 355000);
        $chat = app(AssistantConversationService::class);
        $reply = $chat->handle('Which is cheaper CoQ10 or NADH?')['response'];
        $this->assertCount(2, $reply->products);
        $this->assertStringContainsString('CoQ10 200mg has the lower', $reply->answer);
        $b->update(['price_cents' => 210000]);
        $reply = $chat->handle('Which is cheaper CoQ10 or NADH?')['response'];
        $this->assertStringContainsString('NADH 20mg has the lower', $reply->answer);
        $selected = $chat->handle('NADH', 'product:'.$b->id, (string) $b->id)['response'];
        $this->assertSame($b->id, $selected->products[0]->id);
    }

    public function test_spelling_aside_context_and_factual_negation(): void
    {
        $product = $this->product('CoQ10 200mg');
        $chat = app(AssistantConversationService::class);
        $this->assertSame($product->id, $chat->handle('co q 10')['response']->products[0]->id);
        $chat->handle('What is the weather?');
        $this->assertSame($product->id, $chat->handle('What is its price?')['response']->products[0]->id);
        $reply = $chat->handle('I am not pregnant. What is the CoQ10 price?')['response'];
        $this->assertSame(AssistantResponseType::Answer, $reply->type);
        $this->assertSame($product->id, $reply->products[0]->id);
        $reply = $chat->handle('I am not pregnant. Can I take CoQ10 with warfarin?')['response'];
        $this->assertSame(AssistantResponseType::Boundary, $reply->type);
    }

    public function test_topic_follow_up_and_explicit_product_switch_do_not_reuse_stale_topic(): void
    {
        $post = Post::factory()->create(['title' => 'Label reading guide', 'body' => 'Read the magnesium form on the label.', 'is_published' => true, 'published_at' => now()->subDay()]);
        app(AssistantEligibility::class)->approve($post);
        $product = $this->product('CoQ10 200mg');
        $chat = app(AssistantConversationService::class);
        $this->assertNotEmpty($chat->handle('Where can I read about magnesium?')['response']->contents);
        $this->assertStringContainsString('magnesium product', $chat->handle('yes')['response']->answer);
        $chat->handle('CoQ10');
        $reply = $chat->handle('yes')['response'];
        $this->assertStringContainsString('CoQ10', $reply->answer);
        $this->assertSame($product->id, $chat->handle('What is its price?')['response']->products[0]->id);
        $chat->handle('magnesium');
        $chat->handle('no thanks');
        $this->assertStringNotContainsString('magnesium product', $chat->handle('products')['response']->answer);
    }

    public function test_boundary_invalidates_old_product_actions_and_urgent_detection_survives_turn_cap(): void
    {
        $product = $this->product('Vitamin E 400 IU');
        $chat = app(AssistantConversationService::class);
        $chat->handle('Vitamin E');
        $chat->handle('Vitamin E with warfarin?');
        $this->assertSame([], $chat->handle('How do I use it?')['response']->products);
        $this->assertSame([], $chat->handle('Directions', 'directions', (string) $product->id)['response']->products);
        config(['assistant.limits.max_session_turns' => 1]);
        $reply = $chat->handle('I have difficulty breathing')['response'];
        $this->assertStringContainsString('urgent medical attention', $reply->answer);
    }

    public function test_support_handoffs_and_capability_questions(): void
    {
        $chat = app(AssistantConversationService::class);
        $this->assertStringContainsString('can’t access private order', $chat->handle('Track order 12345')['response']->answer);
        $this->assertStringContainsString('can’t confirm that payment', $chat->handle('Do you accept cash on delivery?')['response']->answer);
        $this->assertStringContainsString('verified discount', $chat->handle('Give me a discount code')['response']->answer);
        $reply = $chat->handle('What can you do?')['response'];
        $this->assertSame(AssistantResponseType::Answer, $reply->type);
        $this->assertNotEmpty($reply->followUps);
        $reply = $chat->handle('aapke paas kaunse products hain')['response'];
        $this->assertSame(AssistantResponseType::Answer, $reply->type);
    }
}
