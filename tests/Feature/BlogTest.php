<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_posts_appear_on_listing_and_single(): void
    {
        $category = PostCategory::query()->create([
            'name' => 'Routines',
            'slug' => 'routines',
            'sort_order' => 1,
        ]);

        $post = Post::factory()->create([
            'title' => 'Evening Wind Down',
            'slug' => 'evening-wind-down',
            'excerpt' => 'Keep the stack simple.',
            'body' => 'Consistency beats complexity.',
            'post_category_id' => $category->id,
            'is_published' => true,
            'published_at' => now()->subHour(),
        ]);

        Post::factory()->draft()->create([
            'title' => 'Hidden Draft',
            'slug' => 'hidden-draft',
        ]);

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertSee('Evening Wind Down', false)
            ->assertSee('Keep the stack simple.', false)
            ->assertDontSee('Hidden Draft', false);

        $this->get(route('blog.show', $post->slug))
            ->assertOk()
            ->assertSee('Evening Wind Down', false)
            ->assertSee('Consistency beats complexity.', false)
            ->assertSee('application/ld+json', false);

        $this->get(route('blog.show', 'hidden-draft'))->assertNotFound();
    }

    public function test_admin_can_manage_posts(): void
    {
        $admin = User::factory()->admin()->create();
        $category = PostCategory::query()->create([
            'name' => 'Ingredients',
            'slug' => 'ingredients',
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.blog.store'), [
                'title' => 'Magnesium Basics',
                'slug' => 'magnesium-basics',
                'excerpt' => 'Form matters.',
                'body' => 'Glycinate is a common evening choice.',
                'post_category_id' => $category->id,
                'tags' => 'magnesium, sleep',
                'is_published' => '1',
                'seo_title' => 'Magnesium Basics',
                'seo_description' => 'A short guide.',
            ])
            ->assertRedirect();

        $post = Post::query()->where('slug', 'magnesium-basics')->first();
        $this->assertNotNull($post);
        $this->assertTrue($post->is_published);
        $this->assertSame(2, $post->tags()->count());

        $this->actingAs($admin)
            ->get(route('admin.blog.index'))
            ->assertOk()
            ->assertSee('Magnesium Basics', false);

        $this->get(route('blog.show', 'magnesium-basics'))
            ->assertOk()
            ->assertSee('Glycinate is a common evening choice.', false);
    }

    public function test_search_includes_blog_articles(): void
    {
        Post::factory()->create([
            'title' => 'Magnesium Evening Guide',
            'slug' => 'magnesium-evening-guide',
            'excerpt' => 'Wind down with intention.',
            'is_published' => true,
            'published_at' => now()->subHour(),
        ]);

        $this->getJson(route('search', ['q' => 'Magnesium']))
            ->assertOk()
            ->assertJsonFragment([
                'type' => 'article',
                'title' => 'Magnesium Evening Guide',
            ]);
    }

    public function test_customer_cannot_access_admin_blog(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('admin.blog.index'))
            ->assertForbidden();
    }

    public function test_journal_shows_featured_once_and_excludes_it_from_grid(): void
    {
        $featured = Post::factory()->create([
            'title' => 'Featured Wellness Story',
            'slug' => 'featured-wellness-story',
            'excerpt' => 'Lead story excerpt.',
            'body' => str_repeat('Wellness reading content here. ', 80),
            'is_published' => true,
            'is_featured' => true,
            'published_at' => now()->subDay(),
        ]);

        $other = Post::factory()->create([
            'title' => 'Secondary Insight',
            'slug' => 'secondary-insight',
            'excerpt' => 'Another article.',
            'is_published' => true,
            'is_featured' => false,
            'published_at' => now()->subHours(2),
        ]);

        $html = $this->get(route('blog.index'))
            ->assertOk()
            ->assertSee('Featured Wellness Story', false)
            ->assertSee('Secondary Insight', false)
            ->assertSee('blog-featured', false)
            ->assertSee('min read', false)
            ->getContent();

        $this->assertSame(1, substr_count($html, 'id="blog-featured-heading"'));
        $this->assertDoesNotMatchRegularExpression(
            '/blog-card__title[^>]*>\s*Featured Wellness Story\s*</',
            $html,
            'Featured article must not also appear in the regular grid on page one.'
        );
        $this->assertStringContainsString('Read Article', $html);

        $this->get(route('blog.index', ['q' => 'Secondary']))
            ->assertOk()
            ->assertSee('Secondary Insight', false)
            ->assertDontSee('blog-featured', false)
            ->assertDontSee('Featured Wellness Story', false);

        $this->assertTrue($featured->fresh()->is_featured);
        $this->assertFalse($other->fresh()->is_featured);
    }

    public function test_admin_featured_flag_is_exclusive(): void
    {
        $admin = User::factory()->admin()->create();

        $first = Post::factory()->create([
            'title' => 'First Feature',
            'slug' => 'first-feature',
            'is_published' => true,
            'is_featured' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.blog.store'), [
                'title' => 'Second Feature',
                'slug' => 'second-feature',
                'excerpt' => 'New lead.',
                'body' => 'Body copy.',
                'is_published' => '1',
                'is_featured' => '1',
            ])
            ->assertRedirect();

        $this->assertFalse($first->fresh()->is_featured);
        $this->assertTrue(Post::query()->where('slug', 'second-feature')->value('is_featured'));
    }
}
