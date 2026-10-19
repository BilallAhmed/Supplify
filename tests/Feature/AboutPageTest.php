<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use App\Support\AboutPageContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AboutPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_page_uses_dedicated_template_and_preserves_url(): void
    {
        Page::factory()->create([
            'title' => 'About',
            'slug' => 'about',
            'body' => "Founded by Muhammad Ahmad.\n\nBuilding Supplify in Pakistan.",
            'is_published' => true,
            'published_at' => now()->subHour(),
        ]);

        $response = $this->get(route('pages.show', 'about'));

        $response->assertOk();
        $response->assertSee('Trusted Nutrition for a Healthier Tomorrow', false);
        $response->assertSee('Discover our journey', false);
        $response->assertSee('A clear purpose from the beginning', false);
        $response->assertSee('What guides our decisions', false);
        $response->assertSee('The work behind our range', false);
        $response->assertSee('Documentation and regulatory preparation', false);
        $response->assertSee('This work is ongoing', false);
        $response->assertSee('USA connections. Pakistan focus.', false);
        $response->assertSee('Building for the years ahead', false);
        $response->assertSee('Get to know Supplify', false);
        $response->assertSee('Explore our range', false);
        $response->assertSee('data-about-page', false);
        $response->assertSee('data-about-journey', false);
        $response->assertSee(route('shop.index'), false);
        $response->assertSee('images/about/about-hero.webp', false);
        $response->assertSee('images/about/about-story.webp', false);
        $response->assertDontSee('about-essentials__row', false);
    }

    public function test_generic_policy_pages_keep_simple_renderer(): void
    {
        Page::factory()->create([
            'title' => 'Privacy',
            'slug' => 'privacy',
            'body' => 'We do not sell personal information.',
            'is_published' => true,
            'published_at' => now()->subHour(),
        ]);

        $this->get(route('pages.show', 'privacy'))
            ->assertOk()
            ->assertSee('Privacy', false)
            ->assertSee('We do not sell personal information.', false)
            ->assertDontSee('data-about-page', false);
    }

    public function test_admin_can_update_about_sections(): void
    {
        $admin = User::factory()->admin()->create();
        $page = Page::factory()->create([
            'title' => 'About',
            'slug' => 'about',
            'body' => 'Original body.',
            'is_published' => true,
            'published_at' => now()->subHour(),
        ]);

        $this->actingAs($admin)
            ->put(route('admin.pages.update', $page), [
                'title' => 'About',
                'slug' => 'about',
                'body' => 'Original body.',
                'is_published' => true,
                'about' => [
                    'hero' => [
                        'enabled' => '1',
                        'eyebrow' => 'About Supplify',
                        'heading' => 'Client heading draft',
                        'intro' => 'Updated intro',
                        'cta_label' => 'Discover our journey',
                        'cta_href' => '#about-approach',
                    ],
                    'purpose' => [
                        'enabled' => '1',
                        'heading' => 'A clear purpose from the beginning',
                        'paragraph_1' => 'Paragraph one',
                        'paragraph_2' => 'Paragraph two',
                        'paragraph_3' => 'Paragraph three',
                    ],
                    'essentials' => [
                        'enabled' => '1',
                        'heading' => 'USA connections. Pakistan focus.',
                        'intro' => 'Connections intro',
                        'closing' => 'Closing line',
                        'blocks' => [
                            ['title' => 'US block', 'body' => 'US body'],
                            ['title' => 'Pakistan block', 'body' => 'Pakistan body'],
                        ],
                    ],
                    'team' => [
                        'enabled' => '0',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.pages.edit', $page));

        $page->refresh();
        $resolved = AboutPageContent::resolve($page);

        $this->assertSame('Client heading draft', $resolved['hero']['heading']);
        $this->assertSame('Updated intro', $resolved['hero']['intro']);
        $this->assertSame('A clear purpose from the beginning', $resolved['purpose']['heading']);
        $this->assertSame('US block', $resolved['essentials']['blocks'][0]['title']);
        $this->assertFalse((bool) $resolved['team']['enabled']);
        $this->assertSame('Original body.', $page->body);

        $this->get(route('pages.show', 'about'))
            ->assertOk()
            ->assertSee('Client heading draft', false)
            ->assertSee('US block', false)
            ->assertDontSee('about-team', false);
    }

    public function test_image_slots_resolve_public_about_assets(): void
    {
        $hero = AboutPageContent::resolveImage('hero');
        $story = AboutPageContent::resolveImage('story');
        $connections = AboutPageContent::resolveImage('connections');

        $this->assertTrue($hero['is_photography']);
        $this->assertNotNull($hero['src']);
        $this->assertStringContainsString('about-hero.webp', (string) $hero['src']);

        $this->assertTrue($story['is_photography']);
        $this->assertNull($story['fallback_product']);

        $this->assertTrue($connections['is_photography']);
        $this->assertStringContainsString('about-connections.webp', (string) $connections['src']);
    }
}
