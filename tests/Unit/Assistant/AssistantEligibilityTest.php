<?php

namespace Tests\Unit\Assistant;

use App\Enums\AssistantSourceType;
use App\Models\AssistantFaq;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\Tag;
use App\Models\User;
use App\Services\Assistant\AssistantEligibility;
use App\Services\Assistant\AssistantSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantEligibilityTest extends TestCase
{
    use RefreshDatabase;

    private AssistantEligibility $eligibility;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eligibility = app(AssistantEligibility::class);
    }

    public function test_published_product_is_ineligible_without_approval(): void
    {
        $product = Product::factory()->create();

        $this->assertFalse($this->eligibility->isProductEligible($product));
    }

    public function test_approved_published_product_is_eligible(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();

        $this->eligibility->approve($product, $admin);

        $this->assertTrue($this->eligibility->isProductEligible($product->fresh()));
    }

    public function test_editorial_draft_tag_blocks_even_when_approved(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $tag = Tag::query()->create([
            'name' => 'Editorial Draft',
            'slug' => 'editorial-draft',
        ]);
        $product->tags()->attach($tag->id);
        $this->eligibility->approve($product, $admin);

        $this->assertFalse($this->eligibility->isProductEligible($product->fresh()->load('tags')));
    }

    public function test_future_and_inactive_products_are_ineligible(): void
    {
        $admin = User::factory()->admin()->create();

        $future = Product::factory()->create(['published_at' => now()->addDay()]);
        $inactive = Product::factory()->create(['is_active' => false]);
        $hidden = Product::factory()->create(['catalog_visibility' => 'hidden']);

        foreach ([$future, $inactive, $hidden] as $product) {
            $this->eligibility->approve($product, $admin);
            $this->assertFalse($this->eligibility->isProductEligible($product->fresh()));
        }
    }

    public function test_posts_and_pages_require_publication_and_approval(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create();
        $draftPost = Post::factory()->draft()->create();
        $page = Page::factory()->create();
        $draftPage = Page::factory()->draft()->create();

        $this->assertFalse($this->eligibility->isPostEligible($post));
        $this->assertFalse($this->eligibility->isPageEligible($page));

        $this->eligibility->approve($post, $admin);
        $this->eligibility->approve($page, $admin);
        $this->eligibility->approve($draftPost, $admin);
        $this->eligibility->approve($draftPage, $admin);

        $this->assertTrue($this->eligibility->isPostEligible($post->fresh()));
        $this->assertTrue($this->eligibility->isPageEligible($page->fresh()));
        $this->assertFalse($this->eligibility->isPostEligible($draftPost->fresh()));
        $this->assertFalse($this->eligibility->isPageEligible($draftPage->fresh()));
    }

    public function test_withdrawing_approval_removes_eligibility(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $this->eligibility->approve($product, $admin);
        $this->eligibility->withdraw($product);

        $this->assertFalse($this->eligibility->isProductEligible($product->fresh()));
    }

    public function test_active_faq_is_eligible_without_separate_approval_row(): void
    {
        $faq = AssistantFaq::query()->create([
            'question' => 'How long does delivery take?',
            'answer' => 'See the Shipping page for current timelines.',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $inactive = AssistantFaq::query()->create([
            'question' => 'Hidden',
            'answer' => 'No',
            'is_active' => false,
        ]);

        $this->assertTrue($this->eligibility->isFaqEligible($faq));
        $this->assertFalse($this->eligibility->isFaqEligible($inactive));
    }

    public function test_category_requires_active_and_approval(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['is_active' => true]);

        $this->assertFalse($this->eligibility->isCategoryEligible($category));
        $this->eligibility->approve($category, $admin);
        $this->assertTrue($this->eligibility->isCategoryEligible($category->fresh()));
    }

    public function test_field_policy_allows_ingredients_and_blocks_orders(): void
    {
        $this->assertTrue($this->eligibility->fieldIsReadable(AssistantSourceType::Product, 'ingredients'));
        $this->assertFalse($this->eligibility->fieldIsReadable(AssistantSourceType::Product, 'orders.total'));
        $this->assertFalse($this->eligibility->fieldIsReadable(AssistantSourceType::Product, 'seo_title'));
    }

    public function test_assistant_defaults_to_disabled(): void
    {
        $settings = app(AssistantSettings::class);

        $this->assertFalse(config('assistant.enabled'));
        $this->assertFalse($settings->isEnabled());
    }
}
