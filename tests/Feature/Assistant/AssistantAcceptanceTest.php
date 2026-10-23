<?php

namespace Tests\Feature\Assistant;

use App\Enums\AssistantIntent;
use App\Enums\AssistantResponseType;
use App\Enums\AssistantSourceType;
use App\Enums\StockStatus;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use App\Services\Assistant\AssistantConversationService;
use App\Services\Assistant\AssistantEligibility;
use App\Services\Assistant\AssistantKnowledgeIndexer;
use App\Services\Assistant\AssistantSettings;
use App\Services\StoreSettings;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Layer 7 acceptance: website-only assistant DoD without AI credentials.
 */
class AssistantAcceptanceTest extends TestCase
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
        ]);
        app(StoreSettings::class)->putMany(['assistant.enabled' => true]);

        $this->eligibility = app(AssistantEligibility::class);
        $this->indexer = app(AssistantKnowledgeIndexer::class);
        $this->admin = User::factory()->admin()->create();
        $this->seed(CmsSeeder::class);
    }

    public function test_eligible_product_links_resolve_and_drafts_never_leak(): void
    {
        $live = $this->approvedProduct([
            'name' => 'Omega Clarity',
            'ingredients' => 'Fish oil',
            'published_at' => now()->subDay(),
            'is_active' => true,
        ]);
        $draft = Product::factory()->create([
            'name' => 'Secret Draft Omega',
            'ingredients' => 'Fish oil',
            'published_at' => null,
            'is_active' => true,
        ]);
        $this->eligibility->approve($draft, $this->admin);
        $this->indexer->refresh(AssistantSourceType::Product, $draft->id);

        $result = app(AssistantConversationService::class)->handle('Omega Clarity');
        $this->assertNotEmpty($result['response']->products);
        $this->assertSame($live->id, $result['response']->products[0]->id);
        $this->assertStringContainsString('/products/', $result['response']->products[0]->url);

        $draftHit = app(AssistantConversationService::class)->handle('Secret Draft Omega');
        foreach ($draftHit['response']->products as $card) {
            $this->assertNotSame($draft->id, $card->id);
        }
    }

    public function test_price_and_stock_edits_appear_in_next_answer(): void
    {
        $product = $this->approvedProduct([
            'name' => 'Zinc Daily',
            'price_cents' => 100000,
            'stock_quantity' => 12,
            'stock_status' => StockStatus::InStock,
        ]);

        $service = app(AssistantConversationService::class);
        $service->handle('Zinc Daily');

        $product->update([
            'price_cents' => 150000,
            'stock_quantity' => 0,
            'stock_status' => StockStatus::OutOfStock,
        ]);

        $price = $service->handle('What is its price?');
        $this->assertSame($product->fresh()->effectivePriceFormatted(), $price['response']->products[0]->price_formatted);

        $stock = $service->handle('Is it in stock?');
        $this->assertStringContainsString('Out of stock', $stock['response']->answer);
    }

    public function test_vitamin_d3_gap_is_honest_without_unrelated_substitution(): void
    {
        $this->approvedProduct(['name' => 'Vitamin E Softgel', 'ingredients' => 'Vitamin E', 'supplement_facts' => null]);

        $result = app(AssistantConversationService::class)->handle('Do you sell vitamin D3?');

        $names = array_map(fn ($p) => $p->name, $result['response']->products);
        $this->assertNotContains('Vitamin E Softgel', $names);
        $this->assertTrue(
            $result['response']->type === AssistantResponseType::Unknown
            || $result['response']->products === []
            || ! str_contains(mb_strtolower($result['response']->answer), 'vitamin e softgel'),
        );
    }

    public function test_medical_boundary_has_no_sales_pressure(): void
    {
        $this->approvedProduct(['name' => 'Magnesium Night', 'ingredients' => 'Magnesium']);

        $result = app(AssistantConversationService::class)->handle(
            'I am pregnant and on blood pressure medication — which magnesium should I take?'
        );

        $this->assertSame(AssistantResponseType::Boundary, $result['response']->type);
        $this->assertSame([], $result['response']->products);
        $this->assertStringContainsString('qualified healthcare professional', $result['response']->answer);
    }

    public function test_deleted_source_excluded_even_if_index_stale_path_runs(): void
    {
        $post = Post::factory()->create([
            'title' => 'Unique Sleep Routines Guide',
            'excerpt' => 'General wellness reading about sleep',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        $this->eligibility->approve($post, $this->admin);
        $this->indexer->refresh(AssistantSourceType::Post, $post->id);

        $before = app(AssistantConversationService::class)->handle('Unique Sleep Routines Guide');
        $this->assertTrue(
            collect($before['response']->contents)->contains(fn ($c) => $c->id === $post->id)
            || collect($before['response']->sources)->contains(fn ($s) => ($s->id ?? null) === $post->id),
            'Expected deleted-target post to be discoverable before delete.',
        );

        $post->delete();

        $after = app(AssistantConversationService::class)->handle('Unique Sleep Routines Guide');
        foreach ($after['response']->contents as $content) {
            $this->assertNotSame($post->id, $content->id);
        }
        $this->assertFalse(
            collect($after['response']->sources)->contains(fn ($s) => ($s->id ?? null) === $post->id),
        );
    }

    public function test_widget_and_api_require_dual_gate_and_no_ai_config(): void
    {
        $this->assertFalse(config()->has('services.openai'));
        $this->assertNull(config('assistant.ai_provider'));
        $this->assertTrue(app(AssistantSettings::class)->isEnabled());

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-assistant-widget', false);

        config(['assistant.enabled' => false]);
        $this->assertFalse(app(AssistantSettings::class)->isEnabled());

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('data-assistant-widget', false);

        $this->postJson(route('assistant.message'), ['message' => 'hello there'])
            ->assertStatus(503);
    }

    public function test_structured_follow_ups_work_in_guided_mode(): void
    {
        $product = $this->approvedProduct([
            'name' => 'Sleep Magnesium',
            'ingredients' => 'Magnesium glycinate',
            'directions' => 'Take one capsule at night.',
        ]);

        $service = app(AssistantConversationService::class);
        $first = $service->handle('Sleep Magnesium');
        $this->assertNotEmpty($first['response']->followUps);
        $this->assertSame($product->id, $first['state']['selected_product_id']);

        $ingredients = $service->handle('Show ingredients');
        $this->assertSame(AssistantIntent::IngredientsOrDirections, $ingredients['response']->intent);
        $this->assertStringContainsString('Magnesium glycinate', $ingredients['response']->answer);
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
}
