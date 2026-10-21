<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_homepage_is_available_without_replacing_live_home(): void
    {
        $this->get(route('home.preview'))
            ->assertOk()
            ->assertSee('Homepage preview', false)
            ->assertSee(config('app.name'), false)
            ->assertSee('data-home-v2', false)
            ->assertSee('Explore the range', false)
            ->assertSee(route('home'), false);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('data-home-v2', false)
            ->assertDontSee('Homepage preview — cinematic direction', false);
    }

    public function test_preview_homepage_is_noindex(): void
    {
        $this->get(route('home.preview'))
            ->assertOk()
            ->assertSee('noindex', false);
    }
}
