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
}
