<?php

namespace Tests\Feature\Assistant;

use App\Enums\AssistantSourceType;
use App\Models\AssistantFaq;
use App\Models\Product;
use App\Models\User;
use App\Services\Assistant\AssistantEligibility;
use App\Services\Assistant\AssistantSettings;
use App\Services\StoreSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantAdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_assistant_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.assistant.edit'))
            ->assertOk()
            ->assertSee('Supplify Assistant', false)
            ->assertSee('ASSISTANT_ENABLED', false)
            ->assertSee('Readable fields', false);
    }

    public function test_customer_cannot_view_or_update_assistant_settings(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('admin.assistant.edit'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->put(route('admin.assistant.update'), ['enabled' => true])
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_assistant_admin(): void
    {
        $this->get(route('admin.assistant.edit'))
            ->assertRedirect();
    }

    public function test_admin_can_toggle_runtime_enable_without_exposing_widget_when_config_off(): void
    {
        config(['assistant.enabled' => false]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.assistant.update'), ['enabled' => true])
            ->assertRedirect(route('admin.assistant.edit'));

        $this->assertTrue((bool) app(StoreSettings::class)->get('assistant.enabled', false));
        $this->assertFalse(app(AssistantSettings::class)->isEnabled());
    }

    public function test_admin_can_approve_and_withdraw_product_source(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $eligibility = app(AssistantEligibility::class);

        $this->actingAs($admin)
            ->post(route('admin.assistant.approvals.store'), [
                'source_type' => AssistantSourceType::Product->value,
                'source_id' => $product->id,
            ])
            ->assertRedirect();

        $this->assertTrue($eligibility->isProductEligible($product->fresh()));

        $this->actingAs($admin)
            ->delete(route('admin.assistant.approvals.destroy'), [
                'source_type' => AssistantSourceType::Product->value,
                'source_id' => $product->id,
            ])
            ->assertRedirect();

        $this->assertFalse($eligibility->isProductEligible($product->fresh()));
    }

    public function test_customer_cannot_approve_sources(): void
    {
        $customer = User::factory()->customer()->create();
        $product = Product::factory()->create();

        $this->actingAs($customer)
            ->post(route('admin.assistant.approvals.store'), [
                'source_type' => AssistantSourceType::Product->value,
                'source_id' => $product->id,
            ])
            ->assertForbidden();
    }

    public function test_admin_policy_allows_faq_management_abilities(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();

        $this->assertTrue($admin->can('manageSettings', AssistantFaq::class));
        $this->assertTrue($admin->can('approveSources', AssistantFaq::class));
        $this->assertTrue($admin->can('create', AssistantFaq::class));
        $this->assertFalse($customer->can('manageSettings', AssistantFaq::class));
        $this->assertFalse($customer->can('approveSources', AssistantFaq::class));
    }
}
