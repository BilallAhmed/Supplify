<?php

namespace Tests\Feature;

use App\Models\SettingsAudit;
use App\Models\User;
use App\Services\TrackingSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTrackingSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_tracking_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.settings.tracking.edit'))
            ->assertOk()
            ->assertSee('Tracking & Scripts', false)
            ->assertSee('Google Tag Manager', false)
            ->assertSee('Save tracking settings', false);
    }

    public function test_customer_cannot_access_tracking_settings(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('admin.settings.tracking.edit'))
            ->assertForbidden();
    }

    public function test_admin_can_save_validated_integrations_and_audit_is_recorded(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.tracking.update'), [
                'environments' => ['production', 'local'],
                'consent_required' => '1',
                'gtm_enabled' => '1',
                'gtm_container_id' => 'gtm-abc123',
                'ga4_enabled' => '1',
                'ga4_measurement_id' => 'g-testmeas01',
                'meta_enabled' => '1',
                'meta_pixel_id' => '123456789012345',
                'custom_snippets' => [
                    [
                        'id' => 'snip-1',
                        'name' => 'Trusted widget',
                        'placement' => 'body_end',
                        'code' => '<script>window.__trustedWidget=true;</script>',
                        'enabled' => '1',
                        'sort_order' => 2,
                        'consent_category' => 'marketing',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.settings.tracking.edit'))
            ->assertSessionHas('flash_success');

        $config = app(TrackingSettings::class)->all();

        $this->assertTrue($config['gtm']['enabled']);
        $this->assertSame('GTM-ABC123', $config['gtm']['container_id']);
        $this->assertSame('G-TESTMEAS01', $config['ga4']['measurement_id']);
        $this->assertSame('123456789012345', $config['meta']['pixel_id']);
        $this->assertCount(1, $config['custom_snippets']);
        $this->assertSame('body_end', $config['custom_snippets'][0]['placement']);
        $this->assertSame($admin->id, $config['updated_by']);

        $this->assertDatabaseHas('settings_audits', [
            'group' => 'tracking',
            'user_id' => $admin->id,
            'summary' => 'Tracking & Scripts settings updated',
        ]);

        $this->assertSame(1, SettingsAudit::query()->where('group', 'tracking')->count());
    }

    public function test_invalid_ids_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.settings.tracking.edit'))
            ->put(route('admin.settings.tracking.update'), [
                'environments' => ['production'],
                'gtm_enabled' => '1',
                'gtm_container_id' => 'NOT-VALID',
                'ga4_enabled' => '0',
                'meta_enabled' => '0',
            ])
            ->assertRedirect(route('admin.settings.tracking.edit'))
            ->assertSessionHasErrors(['gtm_container_id']);
    }

    public function test_php_tags_in_custom_snippets_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.settings.tracking.edit'))
            ->put(route('admin.settings.tracking.update'), [
                'environments' => ['production'],
                'custom_snippets' => [
                    [
                        'name' => 'Bad',
                        'placement' => 'head',
                        'code' => '<?php echo "no"; ?>',
                        'enabled' => '1',
                        'sort_order' => 0,
                        'consent_category' => 'analytics',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.settings.tracking.edit'))
            ->assertSessionHasErrors(['custom_snippets.0.code']);
    }
}
