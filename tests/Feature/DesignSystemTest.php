<?php

namespace Tests\Feature;

use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_uses_storefront_shell(): void
    {
        $this->seed(CmsSeeder::class);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee(config('app.name'), false);
        $response->assertSee('Shop by goal', false);
        $response->assertSee('main-content', false);
    }

    public function test_design_system_page_renders_core_primitives(): void
    {
        $response = $this->get(route('design-system'));

        $response->assertOk();
        $response->assertSee('Design system', false);
        $response->assertSee('Typography', false);
        $response->assertSee('Buttons', false);
        $response->assertSee('Forms', false);
        $response->assertSee('Open sample modal', false);
    }
}
