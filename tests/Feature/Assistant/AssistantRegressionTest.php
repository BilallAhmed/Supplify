<?php

namespace Tests\Feature\Assistant;

use App\Enums\AssistantIntent;
use App\Enums\AssistantResponseType;
use App\Enums\AssistantSourceType;
use App\Models\AssistantAlias;
use App\Models\Post;
use App\Models\Product;
use App\Models\Tag;
use App\Models\User;
use App\Services\Assistant\AssistantConversationService;
use App\Services\Assistant\AssistantEligibility;
use App\Services\Assistant\AssistantIntentRouter;
use App\Services\Assistant\AssistantKnowledgeIndexer;
use App\Services\StoreSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantRegressionTest extends TestCase
{
    use RefreshDatabase;

    private AssistantEligibility $eligibility;

    private AssistantKnowledgeIndexer $indexer;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'assistant.enabled' => true,
            'assistant.search.driver' => 'portable',
            'assistant.limits.session_idle_minutes' => 60,
            'assistant.limits.max_session_turns' => 20,
        ]);
        app(StoreSettings::class)->putMany(['assistant.enabled' => true]);

        $this->eligibility = app(AssistantEligibility::class);
        $this->indexer = app(AssistantKnowledgeIndexer::class);
        $this->admin = User::factory()->admin()->create();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function approvedProduct(array $attributes): Product
    {
        $product = Product::factory()->create($attributes);
        $this->eligibility->approve($product, $this->admin);
        $this->indexer->refresh(AssistantSourceType::Product, $product->id);

        return $product->fresh();
    }

    public function test_catalog_conversation_browse_selection_and_live_follow_up(): void
    {
        $ids = [];
        for ($i = 1; $i <= 5; $i++) {
            $ids[] = $this->approvedProduct(['name' => 'Vitamin C '.$i, 'ingredients' => 'Vitamin C', 'supplement_facts' => null])->id;
        }
        $service = app(AssistantConversationService::class);
        foreach (['What kind of products you have?', 'What types of supplements do you sell?', 'Browse all products'] as $question) {
            $reply = $service->handle($question)['response'];
            $this->assertSame(AssistantResponseType::Answer, $reply->type);
            $this->assertStringContainsString('range', $reply->answer);
            $this->assertContains('browse', array_map(fn ($c) => $c->id, $reply->followUps));
        }
        $first = $service->handle('Vitamins', 'browse-vitamins', 'vitamins')['response'];
        $this->assertCount(3, $first->products);
        $next = collect($first->followUps)->firstWhere('id', 'browse');
        $second = $service->handle('Show more products', $next->id, $next->payload)['response'];
        $this->assertCount(2, $second->products);
        $this->assertSame([], array_intersect(array_column($first->products, 'id'), array_column($second->products, 'id')));
        $id = $second->products[0]->id;
        $service->handle('Choose product', 'product:'.$id, (string) $id);
        $service->handle('thanks');
        $service->handle('yes');
        $price = $service->handle('What is its price?')['response'];
        $this->assertSame($id, $price->products[0]->id);
        $this->assertSame(AssistantIntent::PriceOrAvailability, $price->intent);
    }

    public function test_magnesium_body_mention_recovers_approved_guide_without_draft_product(): void
    {
        $draft = $this->approvedProduct(['name' => 'Magnesium Draft', 'ingredients' => 'Magnesium', 'supplement_facts' => null]);
        $tag = Tag::create(['name' => 'Editorial Draft', 'slug' => 'editorial-draft']);
        $draft->tags()->attach($tag);
        $guide = Post::factory()->create(['title' => 'Reading a label', 'excerpt' => 'Understand your label.', 'body' => '<p>Compare magnesium forms on the label.</p>', 'is_published' => true, 'published_at' => now()->subDay()]);
        $this->eligibility->approve($guide, $this->admin);
        $hidden = Post::factory()->create(['title' => 'Magnesium Secret', 'body' => 'Magnesium secret draft', 'is_published' => true, 'published_at' => now()->subDay()]);
        $service = app(AssistantConversationService::class);
        foreach (['magnesium', 'Tell me about magnesium', 'Do you have magnesium products?', 'magneshium'] as $question) {
            $reply = $service->handle($question)['response'];
            $this->assertSame([], $reply->products);
            $this->assertSame([$guide->id], array_column($reply->contents, 'id'));
            $this->assertStringContainsString('mentions', $reply->answer);
        }
        $reply = $service->handle('Magnesium products', 'topic-products', 'topic-products')['response'];
        $this->assertStringContainsString('can’t confirm', $reply->answer);
        $this->assertNotContains('topic-products', array_column($reply->followUps, 'id'));
        $this->eligibility->withdraw($guide);
        $reply = $service->handle('magnesium')['response'];
        $this->assertSame([], $reply->contents);
    }

    public function test_natural_product_question_and_ingredient_query(): void
    {
        $product = $this->approvedProduct(['name' => 'CoQ10 200mg', 'ingredients' => 'Coenzyme Q10', 'supplement_facts' => null]);
        $service = app(AssistantConversationService::class);
        $reply = $service->handle('Do you have CoQ10?')['response'];
        $this->assertSame($product->id, $reply->products[0]->id);
        $service->reset();
        $reply = $service->handle('Show ingredients of CoQ10')['response'];
        $this->assertStringContainsString('Coenzyme Q10', $reply->answer);
    }

    public function test_energy_support_does_not_route_to_customer_support_and_medical_still_precedes_browse(): void
    {
        $this->assertNotSame(AssistantIntent::DeliveryReturnsContact, app(AssistantIntentRouter::class)->route('energy support products'));
        $reply = app(AssistantConversationService::class)->handle('What products do you have for pregnancy?')['response'];
        $this->assertSame(AssistantResponseType::Boundary, $reply->type);
        $this->assertSame([], $reply->products);
    }

    public function test_audit_new_named_product_overrides_previous_context(): void
    {
        $first = $this->approvedProduct(['name' => 'Magnesium Night', 'ingredients' => 'Magnesium']);
        $second = $this->approvedProduct(['name' => 'Zinc Shield', 'ingredients' => 'Zinc']);
        $service = app(AssistantConversationService::class);
        $service->handle('Magnesium Night');
        $reply = $service->handle('What is the price of Zinc Shield?');
        $this->assertSame($second->id, $reply['response']->products[0]->id);
    }

    public function test_audit_price_choice_answers_price(): void
    {
        $product = $this->approvedProduct(['name' => 'Magnesium Night', 'ingredients' => 'Magnesium']);
        $service = app(AssistantConversationService::class);
        $service->handle('Magnesium Night');
        $reply = $service->handle('What is the price?', 'price', (string) $product->id);
        $this->assertSame(AssistantIntent::PriceOrAvailability, $reply['response']->intent);
    }

    public function test_audit_medical_boundary_precedes_contextual_directions(): void
    {
        $this->approvedProduct(['name' => 'Magnesium Night', 'ingredients' => 'Magnesium', 'directions' => 'Take one tablet daily.']);
        $service = app(AssistantConversationService::class);
        $service->handle('Magnesium Night');
        $reply = $service->handle('How to use it during pregnancy?');
        $this->assertSame(AssistantResponseType::Boundary, $reply['response']->type);
    }

    public function test_missing_named_product_never_reuses_previous_product(): void
    {
        $this->approvedProduct(['name' => 'Vitamin E 400 IU', 'ingredients' => 'Vitamin E', 'supplement_facts' => null]);
        $service = app(AssistantConversationService::class);
        $service->handle('Vitamin E 400 IU');
        $reply = $service->handle('What is the price of Vitamin D3?');
        $this->assertSame([], $reply['response']->products);
    }

    public function test_all_product_quick_reply_actions_and_tampered_selection(): void
    {
        $product = $this->approvedProduct(['name' => 'Zinc Shield', 'ingredients' => 'Zinc citrate', 'directions' => 'Use label directions.']);
        $other = $this->approvedProduct(['name' => 'Unselected product']);
        $service = app(AssistantConversationService::class);
        $service->handle('Zinc Shield');
        foreach (['stock' => AssistantIntent::PriceOrAvailability, 'ingredients' => AssistantIntent::IngredientsOrDirections, 'directions' => AssistantIntent::IngredientsOrDirections, 'articles' => AssistantIntent::RelatedArticles] as $action => $intent) {
            $reply = $service->handle('', $action, (string) $product->id);
            $this->assertSame($intent, $reply['response']->intent);
        }
        $reply = $service->handle('', 'price', (string) $other->id);
        $this->assertSame(AssistantResponseType::Unknown, $reply['response']->type);
    }

    public function test_medical_boundary_precedes_reviewed_alias(): void
    {
        AssistantAlias::create(['phrase' => 'pregnancy price', 'normalized_phrase' => 'pregnancy price', 'locale' => 'en', 'maps_to_intent' => AssistantIntent::PriceOrAvailability, 'is_active' => true]);
        $this->assertSame(AssistantIntent::Boundary, app(AssistantIntentRouter::class)->route('pregnancy price'));
    }
}
