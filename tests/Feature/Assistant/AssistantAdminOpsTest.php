<?php

namespace Tests\Feature\Assistant;

use App\Enums\AssistantIntent;
use App\Jobs\RebuildAssistantKnowledgeJob;
use App\Models\AssistantAlias;
use App\Models\AssistantFaq;
use App\Models\User;
use App\Services\Assistant\AssistantTextNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AssistantAdminOpsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->customer = User::factory()->customer()->create();
    }

    public function test_admin_can_crud_faqs(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.assistant.faqs.index'))
            ->assertOk()
            ->assertSee('Assistant FAQs', false);

        $this->actingAs($this->admin)
            ->post(route('admin.assistant.faqs.store'), [
                'question' => 'What is your delivery policy?',
                'answer' => 'See the published shipping page for current options.',
                'intent' => AssistantIntent::DeliveryReturnsContact->value,
                'aliases_text' => "delivery time\nshipping",
                'sort_order' => 2,
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.assistant.faqs.index'));

        $faq = AssistantFaq::query()->where('question', 'What is your delivery policy?')->first();
        $this->assertNotNull($faq);
        $this->assertSame(['delivery time', 'shipping'], $faq->aliases);
        $this->assertTrue($faq->is_active);

        $this->actingAs($this->admin)
            ->put(route('admin.assistant.faqs.update', $faq), [
                'question' => 'What is your delivery policy?',
                'answer' => 'Updated shipping guidance from the published page.',
                'intent' => AssistantIntent::DeliveryReturnsContact->value,
                'aliases_text' => 'shipping',
                'sort_order' => 1,
                'is_active' => false,
            ])
            ->assertRedirect(route('admin.assistant.faqs.index'));

        $this->assertFalse($faq->fresh()->is_active);
        $this->assertSame(['shipping'], $faq->fresh()->aliases);

        $this->actingAs($this->admin)
            ->delete(route('admin.assistant.faqs.destroy', $faq))
            ->assertRedirect(route('admin.assistant.faqs.index'));

        $this->assertSoftDeleted($faq);
    }

    public function test_customer_cannot_manage_faqs(): void
    {
        $faq = AssistantFaq::query()->create([
            'question' => 'Private FAQ',
            'answer' => 'Not for customers.',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($this->customer)
            ->get(route('admin.assistant.faqs.index'))
            ->assertForbidden();

        $this->actingAs($this->customer)
            ->post(route('admin.assistant.faqs.store'), [
                'question' => 'Hack',
                'answer' => 'Nope',
                'is_active' => true,
            ])
            ->assertForbidden();

        $this->actingAs($this->customer)
            ->delete(route('admin.assistant.faqs.destroy', $faq))
            ->assertForbidden();
    }

    public function test_admin_can_crud_aliases_and_normalizes_phrase(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.assistant.aliases.store'), [
                'phrase' => '  Kitne Ka Hai  ',
                'locale' => 'roman_ur',
                'maps_to_intent' => AssistantIntent::PriceOrAvailability->value,
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.assistant.aliases.index'));

        $alias = AssistantAlias::query()->where('phrase', 'Kitne Ka Hai')->first()
            ?? AssistantAlias::query()->latest('id')->first();

        $this->assertNotNull($alias);
        $this->assertSame(
            app(AssistantTextNormalizer::class)->normalize('Kitne Ka Hai'),
            $alias->normalized_phrase,
        );
        $this->assertSame(AssistantIntent::PriceOrAvailability, $alias->maps_to_intent);

        $this->actingAs($this->admin)
            ->put(route('admin.assistant.aliases.update', $alias), [
                'phrase' => 'price check',
                'locale' => 'en',
                'maps_to_intent' => AssistantIntent::PriceOrAvailability->value,
                'is_active' => false,
            ])
            ->assertRedirect(route('admin.assistant.aliases.index'));

        $this->assertFalse($alias->fresh()->is_active);

        $this->actingAs($this->admin)
            ->delete(route('admin.assistant.aliases.destroy', $alias))
            ->assertRedirect(route('admin.assistant.aliases.index'));

        $this->assertDatabaseMissing('assistant_aliases', ['id' => $alias->id]);
    }

    public function test_customer_cannot_manage_aliases_or_rebuild(): void
    {
        $this->actingAs($this->customer)
            ->get(route('admin.assistant.aliases.index'))
            ->assertForbidden();

        $this->actingAs($this->customer)
            ->post(route('admin.assistant.rebuild'))
            ->assertForbidden();
    }

    public function test_admin_can_queue_and_sync_rebuild(): void
    {
        Queue::fake();

        $this->actingAs($this->admin)
            ->post(route('admin.assistant.rebuild'))
            ->assertRedirect(route('admin.assistant.edit'));

        Queue::assertPushed(RebuildAssistantKnowledgeJob::class);

        $this->actingAs($this->admin)
            ->post(route('admin.assistant.rebuild'), ['sync' => '1'])
            ->assertRedirect(route('admin.assistant.edit'))
            ->assertSessionHas('status');
    }

    public function test_settings_page_links_to_faq_alias_and_rebuild(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.assistant.edit'))
            ->assertOk()
            ->assertSee('Knowledge controls', false)
            ->assertSee(route('admin.assistant.faqs.index'), false)
            ->assertSee(route('admin.assistant.aliases.index'), false)
            ->assertSee('Queue knowledge rebuild', false);
    }
}
