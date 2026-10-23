<?php

namespace Tests\Feature\Assistant;

use App\Enums\AssistantIntent;
use App\Enums\AssistantResponseType;
use App\Enums\AssistantSourceType;
use App\Models\AssistantAlias;
use App\Models\AssistantFaq;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\User;
use App\Services\Assistant\AssistantEligibility;
use App\Services\Assistant\AssistantKnowledgeIndexer;
use App\Services\Assistant\AssistantKnowledgeSearch;
use App\Services\Assistant\AssistantTextNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantKnowledgeSearchTest extends TestCase
{
    use RefreshDatabase;

    private AssistantEligibility $eligibility;

    private AssistantKnowledgeIndexer $indexer;

    private AssistantKnowledgeSearch $search;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['assistant.search.driver' => 'portable']);
        $this->eligibility = app(AssistantEligibility::class);
        $this->indexer = app(AssistantKnowledgeIndexer::class);
        $this->search = app(AssistantKnowledgeSearch::class);
        $this->admin = User::factory()->admin()->create();
    }

    public function test_exact_product_name_ranks_highest(): void
    {
        $product = $this->approvedProduct([
            'name' => 'Magnesium Night',
            'ingredients' => 'Magnesium glycinate',
        ]);
        $other = $this->approvedProduct([
            'name' => 'Daily Greens',
            'ingredients' => 'Spinach blend',
        ]);

        $outcome = $this->search->search('Magnesium Night');

        $this->assertSame('high', $outcome->confidence);
        $this->assertSame(AssistantResponseType::Answer, $outcome->suggestedType);
        $this->assertSame($product->id, $outcome->hits[0]->id);
        $this->assertContains($outcome->hits[0]->matchKind, ['exact_name', 'title_phrase', 'alias']);
        $this->assertFalse(collect($outcome->hits)->contains(fn ($hit) => $hit->id === $other->id && $hit->score >= $outcome->hits[0]->score));
    }

    public function test_vitamin_d3_does_not_match_vitamin_e_product(): void
    {
        $d3 = $this->approvedProduct([
            'name' => 'Bone Support D3',
            'ingredients' => 'Vitamin D3 25 mcg',
            'supplement_facts' => 'Vitamin D3 1000 IU',
        ]);
        $e = $this->approvedProduct([
            'name' => 'Antioxidant E',
            'ingredients' => 'Vitamin E 15 mg',
            'supplement_facts' => 'Vitamin E (as tocopherol)',
        ]);

        $outcome = $this->search->search('Vitamin D3');

        $ids = collect($outcome->hits)->pluck('id');
        $this->assertTrue($ids->contains($d3->id));
        $this->assertFalse($ids->contains($e->id));
    }

    public function test_bare_vitamin_query_asks_for_clarification_or_unknown_without_dumping_all(): void
    {
        $this->approvedProduct([
            'name' => 'Vitamin D3 Softgel',
            'ingredients' => 'Vitamin D3',
        ]);
        $this->approvedProduct([
            'name' => 'Vitamin E Capsule',
            'ingredients' => 'Vitamin E',
        ]);

        $outcome = $this->search->search('vitamin');

        $this->assertContains($outcome->suggestedType, [
            AssistantResponseType::Clarification,
            AssistantResponseType::Unknown,
        ]);
        $this->assertLessThanOrEqual(2, count($outcome->hits));
    }

    public function test_alias_resolves_roman_urdu_and_urdu_phrases(): void
    {
        $normalizer = app(AssistantTextNormalizer::class);
        AssistantAlias::query()->create([
            'phrase' => 'kitne ka hai',
            'normalized_phrase' => $normalizer->normalize('kitne ka hai'),
            'locale' => 'roman_ur',
            'maps_to_intent' => AssistantIntent::PriceOrAvailability,
            'is_active' => true,
        ]);
        AssistantAlias::query()->create([
            'phrase' => 'نیند',
            'normalized_phrase' => $normalizer->normalize('نیند'),
            'locale' => 'ur',
            'maps_to_intent' => AssistantIntent::FindProduct,
            'is_active' => true,
        ]);

        $price = $this->search->search('kitne ka hai magnesium');
        $this->assertSame(AssistantIntent::PriceOrAvailability, $price->intent);
        $this->assertContains('kitne ka hai', $price->resolvedAliases);

        $sleep = $this->search->search('نیند');
        $this->assertSame(AssistantIntent::FindProduct, $sleep->intent);
        $this->assertContains('نیند', $sleep->resolvedAliases);
    }

    public function test_ambiguous_close_scores_return_clarification_choices(): void
    {
        $this->approvedProduct(['name' => 'Omega Complex 1000', 'ingredients' => 'Fish oil']);
        $this->approvedProduct(['name' => 'Omega Complex Extra', 'ingredients' => 'Fish oil concentrate']);

        $outcome = $this->search->search('Omega Complex');

        $this->assertSame(AssistantResponseType::Clarification, $outcome->suggestedType);
        $this->assertSame(AssistantIntent::Clarify, $outcome->intent);
        $this->assertNotEmpty($outcome->clarifications);
        $this->assertGreaterThanOrEqual(2, count($outcome->hits));
    }

    public function test_unrelated_terms_and_missing_products_return_unknown(): void
    {
        $this->approvedProduct(['name' => 'Magnesium Night', 'ingredients' => 'Magnesium']);

        $outcome = $this->search->search('Vitamin D3 softgels');

        $this->assertSame(AssistantResponseType::Unknown, $outcome->suggestedType);
        $this->assertSame('none', $outcome->confidence);
        $this->assertSame([], $outcome->hits);
    }

    public function test_goal_category_match_requires_approval(): void
    {
        $approved = Category::factory()->create([
            'name' => 'Sleep Recovery',
            'slug' => 'sleep-recovery',
            'is_active' => true,
        ]);
        $unapproved = Category::factory()->create([
            'name' => 'Sleep Draft',
            'slug' => 'sleep-draft',
            'is_active' => true,
        ]);
        $this->eligibility->approve($approved, $this->admin);
        $this->indexer->refresh(AssistantSourceType::Category, $approved->id);

        $outcome = $this->search->search('sleep support');

        $ids = collect($outcome->hits)->where('type', AssistantSourceType::Category)->pluck('id');
        $this->assertTrue($ids->contains($approved->id));
        $this->assertFalse($ids->contains($unapproved->id));
    }

    public function test_faq_and_policy_retrieval(): void
    {
        $page = Page::factory()->create([
            'title' => 'Shipping',
            'slug' => 'shipping',
            'body' => 'Domestic delivery timelines.',
        ]);
        $this->eligibility->approve($page, $this->admin);
        $this->indexer->refresh(AssistantSourceType::Page, $page->id);

        AssistantFaq::query()->create([
            'question' => 'How long does delivery take?',
            'answer' => 'See the Shipping page for current timelines.',
            'aliases' => ['delivery time', 'shipping time'],
            'is_active' => true,
        ]);

        $outcome = $this->search->search('delivery time');

        $types = collect($outcome->hits)->pluck('type');
        $this->assertTrue(
            $types->contains(AssistantSourceType::Faq) || $types->contains(AssistantSourceType::Page)
        );
    }

    public function test_results_are_bounded(): void
    {
        for ($i = 1; $i <= 8; $i++) {
            $this->approvedProduct([
                'name' => "Focus Blend {$i}",
                'ingredients' => 'Caffeine free focus botanicals',
            ]);
        }

        $outcome = $this->search->search('Focus Blend');
        $max = (int) config('assistant.limits.max_products_per_answer', 3);

        $this->assertLessThanOrEqual(max($max, 4), count($outcome->hits));
    }

    public function test_medical_boundary_phrases_do_not_return_products(): void
    {
        $this->approvedProduct(['name' => 'Magnesium Night', 'ingredients' => 'Magnesium']);

        $outcome = $this->search->search('prescribe magnesium for my pregnancy');

        $this->assertSame(AssistantIntent::Boundary, $outcome->intent);
        $this->assertSame(AssistantResponseType::Boundary, $outcome->suggestedType);
        $this->assertSame([], $outcome->hits);
    }

    public function test_search_driver_is_portable_in_tests(): void
    {
        $this->assertSame('portable_token', $this->search->driverName());
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
