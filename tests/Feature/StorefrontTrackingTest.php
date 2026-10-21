<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TrackingSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_enabled_gtm_renders_in_storefront_head_and_body_when_environment_allows(): void
    {
        $this->app['env'] = 'production';

        $admin = User::factory()->admin()->create();
        app(TrackingSettings::class)->save([
            'environments' => ['production'],
            'consent_required' => false,
            'gtm' => ['enabled' => true, 'container_id' => 'GTM-TEST123'],
            'ga4' => ['enabled' => false, 'measurement_id' => null],
            'meta' => ['enabled' => false, 'pixel_id' => null],
            'custom_snippets' => [],
        ], $admin);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('googletagmanager.com/gtm.js?id=', false);
        $response->assertSee('GTM-TEST123', false);
        $response->assertSee('googletagmanager.com/ns.html?id=GTM-TEST123', false);
    }

    public function test_tracking_does_not_render_in_disallowed_environments(): void
    {
        $this->app['env'] = 'local';

        $admin = User::factory()->admin()->create();
        app(TrackingSettings::class)->save([
            'environments' => ['production'],
            'consent_required' => false,
            'gtm' => ['enabled' => true, 'container_id' => 'GTM-TEST123'],
            'ga4' => ['enabled' => false, 'measurement_id' => null],
            'meta' => ['enabled' => false, 'pixel_id' => null],
            'custom_snippets' => [],
        ], $admin);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('GTM-TEST123', false);
    }

    public function test_native_ga4_and_meta_are_skipped_when_gtm_is_active(): void
    {
        $this->app['env'] = 'production';

        $admin = User::factory()->admin()->create();
        app(TrackingSettings::class)->save([
            'environments' => ['production'],
            'consent_required' => false,
            'gtm' => ['enabled' => true, 'container_id' => 'GTM-TEST123'],
            'ga4' => ['enabled' => true, 'measurement_id' => 'G-ABCDEFG123'],
            'meta' => ['enabled' => true, 'pixel_id' => '987654321098765'],
            'custom_snippets' => [],
        ], $admin);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('GTM-TEST123', $html);
        $this->assertStringNotContainsString('G-ABCDEFG123', $html);
        $this->assertStringNotContainsString('987654321098765', $html);
        $this->assertStringNotContainsString('fbevents.js', $html);
    }

    public function test_consent_gates_non_essential_scripts_until_accepted(): void
    {
        $this->app['env'] = 'production';

        $admin = User::factory()->admin()->create();
        app(TrackingSettings::class)->save([
            'environments' => ['production'],
            'consent_required' => true,
            'gtm' => ['enabled' => true, 'container_id' => 'GTM-TEST123'],
            'ga4' => ['enabled' => false, 'measurement_id' => null],
            'meta' => ['enabled' => false, 'pixel_id' => null],
            'custom_snippets' => [],
        ], $admin);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('GTM-TEST123', false)
            ->assertSee('Cookies &amp; tracking', false);

        $this->withUnencryptedCookie('supplify_consent', json_encode([
            'analytics' => true,
            'marketing' => false,
            'decided' => true,
        ]))
            ->get(route('home'))
            ->assertOk()
            ->assertSee('GTM-TEST123', false);
    }

    public function test_custom_snippet_respects_placement_and_is_unescaped(): void
    {
        $this->app['env'] = 'production';

        $admin = User::factory()->admin()->create();
        app(TrackingSettings::class)->save([
            'environments' => ['production'],
            'consent_required' => false,
            'gtm' => ['enabled' => false, 'container_id' => null],
            'ga4' => ['enabled' => false, 'measurement_id' => null],
            'meta' => ['enabled' => false, 'pixel_id' => null],
            'custom_snippets' => [
                [
                    'id' => 'a1',
                    'name' => 'Marker',
                    'placement' => 'body_end',
                    'code' => '<script id="custom-marker">window.__customMarker=1;</script>',
                    'enabled' => true,
                    'sort_order' => 1,
                    'consent_category' => 'marketing',
                ],
            ],
        ], $admin);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('<script id="custom-marker">window.__customMarker=1;</script>', $html);
        $this->assertStringNotContainsString('&lt;script id="custom-marker"', $html);
    }

    public function test_admin_layout_does_not_include_storefront_tracking(): void
    {
        $this->app['env'] = 'production';

        $admin = User::factory()->admin()->create();
        app(TrackingSettings::class)->save([
            'environments' => ['production'],
            'consent_required' => false,
            'gtm' => ['enabled' => true, 'container_id' => 'GTM-TEST123'],
            'ga4' => ['enabled' => false, 'measurement_id' => null],
            'meta' => ['enabled' => false, 'pixel_id' => null],
            'custom_snippets' => [],
        ], $admin);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('GTM-TEST123', false)
            ->assertDontSee('googletagmanager.com/gtm.js', false);
    }
}
