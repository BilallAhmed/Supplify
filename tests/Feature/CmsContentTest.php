<?php

namespace Tests\Feature;

use App\Models\HomepageSection;
use App\Models\NewsletterSubscriber;
use App\Models\Page;
use App\Models\Product;
use App\Models\User;
use App\Support\HomepageLayout;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_enabled_sections_and_featured_products(): void
    {
        $this->seed(CmsSeeder::class);

        Product::factory()->create([
            'name' => 'Featured Omega',
            'is_featured' => true,
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);

        HomepageSection::query()->where('key', 'promo')->update(['is_enabled' => false]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Featured Omega', false);
        $response->assertSee('Shop by goal', false);
        $response->assertSee('Stay in the loop', false);
        $response->assertSee('Our journey', false);
        $response->assertSee('Discover Our Approach', false);
        $response->assertDontSee('From the community', false);
        $response->assertDontSee('Everyday essentials, thoughtfully priced', false);
        $response->assertSee('data-home-hero', false);
        $response->assertSee('data-home-approach', false);
    }

    public function test_published_cms_page_is_public_and_draft_is_hidden(): void
    {
        $page = Page::factory()->create([
            'title' => 'Shipping Policy',
            'slug' => 'shipping-policy',
            'body' => 'We ship across the US.',
        ]);

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertSee('Shipping Policy', false)
            ->assertSee('We ship across the US.', false);

        $draft = Page::factory()->draft()->create(['slug' => 'secret-page']);
        $this->get(route('pages.show', $draft->slug))->assertNotFound();
    }

    public function test_admin_can_manage_pages_and_homepage_sections(): void
    {
        $admin = User::factory()->admin()->create();
        $this->seed(CmsSeeder::class);

        $this->actingAs($admin)
            ->post(route('admin.pages.store'), [
                'title' => 'Careers',
                'body' => 'Join the team.',
                'is_published' => true,
            ])
            ->assertRedirect();

        $page = Page::query()->where('slug', 'careers')->first();
        $this->assertNotNull($page);
        $this->assertTrue($page->is_published);

        $section = HomepageSection::query()->where('key', 'hero')->firstOrFail();

        $data = ['is_enabled' => true];
        $values = HomepageLayout::values($section);
        foreach (HomepageLayout::fields('hero') as $field) {
            data_set($data, $field['path'], data_get($values, $field['path']));
        }
        $data['config']['slides'][0]['title'] = 'Updated Hero';
        $this->actingAs($admin)->put(route('admin.homepage.update', $section), $data)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.homepage.edit', ['section' => 'hero']));
        $this->assertSame('Updated Hero', $section->fresh()->config['slides'][0]['title']);
    }

    public function test_newsletter_subscription_stores_email(): void
    {
        $this->from(route('home'))
            ->post(route('newsletter.store'), [
                'email' => 'reader@example.com',
            ])
            ->assertRedirect(route('home'));

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'reader@example.com',
            'is_active' => true,
        ]);

        $this->assertSame(1, NewsletterSubscriber::query()->count());

        $this->from(route('home'))
            ->post(route('newsletter.store'), [
                'email' => 'reader@example.com',
            ])
            ->assertRedirect(route('home'));

        $this->assertSame(1, NewsletterSubscriber::query()->count());
    }
}
