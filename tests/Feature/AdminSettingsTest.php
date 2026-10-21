<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CheckoutService;
use App\Services\StoreSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_and_update_store_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Save settings', false)
            ->assertDontSee('Coming next', false);

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'currency' => 'USD',
                'shipping_method' => 'flat',
                'flat_rate' => '7.50',
                'free_over' => '100.00',
                'tax_rate_percent' => '8.25',
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $settings = app(StoreSettings::class);
        $this->assertSame(750, $settings->flatRateCents());
        $this->assertSame(10000, $settings->freeOverCents());
        $this->assertSame(8.25, $settings->taxRatePercent());

        $quote = app(CheckoutService::class)->quote([
            'subtotal_cents' => 5000,
            'discount_cents' => 0,
            'lines' => collect(),
        ]);

        $this->assertSame(750, $quote['shipping_cents']);
        $this->assertSame((int) round(5750 * 0.0825), $quote['tax_cents']);
    }

    public function test_customer_cannot_access_settings(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('admin.settings.edit'))
            ->assertForbidden();
    }
}
