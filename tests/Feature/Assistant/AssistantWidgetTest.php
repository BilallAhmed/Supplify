<?php

namespace Tests\Feature\Assistant;

use App\Services\StoreSettings;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
    }

    public function test_widget_is_absent_when_config_disabled(): void
    {
        config(['assistant.enabled' => false]);
        app(StoreSettings::class)->putMany(['assistant.enabled' => true]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('data-assistant-widget', false)
            ->assertDontSee('data-assistant-launcher', false);
    }

    public function test_widget_is_absent_when_runtime_disabled(): void
    {
        config(['assistant.enabled' => true]);
        app(StoreSettings::class)->putMany(['assistant.enabled' => false]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('data-assistant-widget', false)
            ->assertDontSee('data-assistant-launcher', false);
    }

    public function test_widget_renders_when_dual_gate_enabled(): void
    {
        config([
            'assistant.enabled' => true,
            'assistant.public_name' => 'Supplify Assistant',
            'assistant.tagline' => 'Website help for products and policies',
        ]);
        app(StoreSettings::class)->putMany(['assistant.enabled' => true]);

        $response = $this->get(route('home'))->assertOk();

        $response->assertSee('data-assistant-widget', false);
        $response->assertSee('data-assistant-launcher', false);
        $response->assertSee('data-assistant-panel', false);
        $response->assertSee('supplifyAssistant', false);
        $response->assertSee('role="dialog"', false);
        $response->assertSee('Find a product', false);
        $response->assertSee('not medical advice', false);
        // @js() JSON-encodes URLs, so path slashes appear escaped in HTML.
        $response->assertSee('assistant\/message', false);
        $response->assertSee('assistant\/reset', false);
        $response->assertSee('Supplify Assistant', false);
        $response->assertSee('Website help for products and policies', false);
    }

    public function test_widget_stays_gated_on_shop_when_disabled(): void
    {
        config(['assistant.enabled' => false]);
        app(StoreSettings::class)->putMany(['assistant.enabled' => false]);

        $this->get(route('shop.index'))
            ->assertOk()
            ->assertDontSee('data-assistant-widget', false);
    }
}
