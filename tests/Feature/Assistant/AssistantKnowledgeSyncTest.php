<?php

namespace Tests\Feature\Assistant;

use App\Enums\AssistantSourceType;
use App\Jobs\RefreshAssistantDocumentJob;
use App\Models\AssistantDocument;
use App\Models\AssistantFaq;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\Tag;
use App\Models\User;
use App\Services\Assistant\AssistantEligibility;
use App\Services\Assistant\AssistantKnowledgeIndexer;
use App\Services\Assistant\AssistantKnowledgeReader;
use App\Services\Assistant\AssistantKnowledgeSync;
use App\Services\CatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class AssistantKnowledgeSyncTest extends TestCase
{
    use RefreshDatabase;

    private AssistantEligibility $eligibility;

    private AssistantKnowledgeIndexer $indexer;

    private AssistantKnowledgeReader $reader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eligibility = app(AssistantEligibility::class);
        $this->indexer = app(AssistantKnowledgeIndexer::class);
        $this->reader = app(AssistantKnowledgeReader::class);
    }

    public function test_approved_product_is_indexed_with_chunks(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create([
            'name' => 'Magnesium Night',
            'ingredients' => 'Magnesium glycinate 200 mg',
            'directions' => 'Take one capsule nightly.',
        ]);
        $this->eligibility->approve($product, $admin);

        $document = $this->indexer->refresh(AssistantSourceType::Product, $product->id);

        $this->assertSame(AssistantDocument::STATUS_ACTIVE, $document->status);
        $this->assertTrue($document->is_approved);
        $this->assertNotEmpty($document->content_revision);
        $this->assertTrue($document->chunks()->count() >= 1);
        $this->assertStringContainsString('magnesium', (string) $document->searchable_text);
    }

    public function test_unapproved_and_unpublished_sources_are_removed_from_index(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $this->eligibility->approve($product, $admin);
        $this->indexer->refresh(AssistantSourceType::Product, $product->id);

        $this->eligibility->withdraw($product);
        $removed = $this->indexer->refresh(AssistantSourceType::Product, $product->id);
        $this->assertSame(AssistantDocument::STATUS_REMOVED, $removed->status);
        $this->assertSame(0, $removed->chunks()->count());

        $this->eligibility->approve($product, $admin);
        $product->update(['is_active' => false]);
        $unpublished = $this->indexer->refresh(AssistantSourceType::Product, $product->id);
        $this->assertSame(AssistantDocument::STATUS_REMOVED, $unpublished->status);
    }

    public function test_soft_deleted_product_cannot_be_read_from_index(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $this->eligibility->approve($product, $admin);
        $document = $this->indexer->refresh(AssistantSourceType::Product, $product->id);

        $product->delete();
        $this->indexer->refresh(AssistantSourceType::Product, $product->id);

        $this->assertNull($this->reader->usableDocument($document->fresh(['chunks'])));
    }

    public function test_stale_index_omits_excerpts_and_live_eligibility_still_gates(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['ingredients' => 'Original formula']);
        $this->eligibility->approve($product, $admin);
        $document = $this->indexer->refresh(AssistantSourceType::Product, $product->id);

        // Bypass observers so the index stays intentionally stale for this assertion.
        Product::withoutEvents(function () use ($product): void {
            $product->update(['ingredients' => 'Updated formula with zinc']);
        });
        $usable = $this->reader->usableDocument($document->fresh(['chunks']), queueRefreshIfStale: false);

        $this->assertNotNull($usable);
        $this->assertTrue($usable['index_stale']);
        $this->assertSame([], $usable['excerpts']);
    }

    public function test_category_and_tag_relationship_changes_refresh_product_knowledge(): void
    {
        Bus::fake([RefreshAssistantDocumentJob::class]);

        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Sleep Recovery']);
        $product = Product::factory()->create(['name' => 'Calm Complex']);
        $this->eligibility->approve($product, $admin);
        $this->eligibility->approve($category, $admin);

        app(CatalogService::class)->updateProduct(
            $product,
            [
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'price' => '25.00',
                'stock_quantity' => 10,
                'low_stock_threshold' => 5,
                'is_active' => true,
                'weight_unit' => 'g',
                'dimension_unit' => 'cm',
                'catalog_visibility' => 'visible',
                'product_type' => 'simple',
                'manage_stock' => true,
                'requires_shipping' => true,
                'tags' => 'sleep, nighttime',
            ],
            categoryIds: [$category->id],
        );

        Bus::assertDispatched(RefreshAssistantDocumentJob::class, function (RefreshAssistantDocumentJob $job) use ($product) {
            return $job->sourceType === AssistantSourceType::Product->value
                && $job->sourceId === $product->id;
        });
    }

    public function test_scheduled_publication_becomes_indexable_after_reconcile(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create([
            'title' => 'Future Sleep Guide',
            'is_published' => true,
            'published_at' => now()->addDay(),
        ]);
        $this->eligibility->approve($post, $admin);

        $before = $this->indexer->refresh(AssistantSourceType::Post, $post->id);
        $this->assertSame(AssistantDocument::STATUS_REMOVED, $before->status);

        $this->travel(2)->days();
        $stats = $this->indexer->reconcile();

        $document = AssistantDocument::query()
            ->where('source_type', AssistantSourceType::Post->value)
            ->where('source_id', $post->id)
            ->first();

        $this->assertSame(AssistantDocument::STATUS_ACTIVE, $document?->status);
        $this->assertGreaterThanOrEqual(1, $stats['refreshed'] + $stats['scheduled_published']);
    }

    public function test_out_of_order_job_does_not_clobber_newer_index(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['short_description' => 'First']);
        $this->eligibility->approve($product, $admin);

        $first = $this->indexer->refresh(AssistantSourceType::Product, $product->id);
        $revision = $first->content_revision;

        $product->update(['short_description' => 'Second revision text']);
        $second = $this->indexer->refresh(AssistantSourceType::Product, $product->id);
        $this->assertNotSame($revision, $second->content_revision);

        // Older job metadata arrives late with previous revision and earlier timestamp.
        $result = $this->indexer->refreshIfCurrent(
            AssistantSourceType::Product,
            $product->id,
            $revision,
            now()->subMinute()->toIso8601String(),
        );

        $this->assertSame($second->fresh()->content_revision, $result?->content_revision);
        $this->assertStringContainsString('second revision', (string) $result?->searchable_text);
    }

    public function test_repeated_refresh_is_idempotent_for_same_revision(): void
    {
        $admin = User::factory()->admin()->create();
        $faq = AssistantFaq::query()->create([
            'question' => 'Where is shipping info?',
            'answer' => 'See the Shipping page.',
            'is_active' => true,
        ]);

        $one = $this->indexer->refresh(AssistantSourceType::Faq, $faq->id);
        $two = $this->indexer->refresh(AssistantSourceType::Faq, $faq->id);

        $this->assertSame($one->content_revision, $two->content_revision);
        $this->assertSame(1, AssistantDocument::query()->where('source_type', 'faq')->where('source_id', $faq->id)->count());
    }

    public function test_live_product_fallback_finds_eligible_product_before_or_without_index_row(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['name' => 'Zinc Shield Daily']);
        $this->eligibility->approve($product, $admin);

        AssistantDocument::query()->where('source_type', 'product')->where('source_id', $product->id)->delete();

        $hits = $this->reader->liveProductFallback('Zinc Shield');
        $this->assertTrue($hits->contains('id', $product->id));
    }

    public function test_rebuild_command_indexes_approved_page(): void
    {
        $admin = User::factory()->admin()->create();
        $page = Page::factory()->create(['title' => 'Returns Policy', 'slug' => 'returns-policy-test']);
        $this->eligibility->approve($page, $admin);

        Artisan::call('assistant:rebuild-knowledge', ['--sync' => true]);

        $document = AssistantDocument::query()
            ->where('source_type', AssistantSourceType::Page->value)
            ->where('source_id', $page->id)
            ->first();

        $this->assertNotNull($document);
        $this->assertSame(AssistantDocument::STATUS_ACTIVE, $document->status);
        $this->assertNotNull($this->indexer->status()['last_successful_sync']);
    }

    public function test_editorial_draft_never_stays_active_in_index(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $tag = Tag::query()->create(['name' => 'Editorial Draft', 'slug' => 'editorial-draft']);
        $product->tags()->attach($tag->id);
        $this->eligibility->approve($product, $admin);

        $document = $this->indexer->refresh(AssistantSourceType::Product, $product->id);

        $this->assertSame(AssistantDocument::STATUS_REMOVED, $document->status);
        $this->assertNull($this->reader->usableDocument($document));
    }

    public function test_sync_dispatcher_queues_after_commit_metadata(): void
    {
        Bus::fake([RefreshAssistantDocumentJob::class]);

        $product = Product::factory()->create();
        app(AssistantKnowledgeSync::class)->queueProduct($product->id);

        Bus::assertDispatched(RefreshAssistantDocumentJob::class, function (RefreshAssistantDocumentJob $job) use ($product) {
            return $job->sourceId === $product->id
                && $job->sourceType === 'product'
                && $job->dispatchedAt !== null;
        });
    }
}
