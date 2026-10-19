<?php

namespace Tests\Feature;

use App\Models\Post;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageEducationFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_education_shows_three_latest_posts_and_blog_cta(): void
    {
        $this->seed(CmsSeeder::class);

        Post::factory()->count(5)->create([
            'is_published' => true,
            'published_at' => now()->subHour(),
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Learn before you buy', false);
        $response->assertSee('home-education__grid', false);
        $response->assertSee('View all articles', false);
        $response->assertSee(route('blog.index'), false);
        $response->assertDontSee('Read the journal', false);

        $this->assertSame(
            3,
            substr_count($response->getContent(), 'home-education__card')
        );
    }

    public function test_draft_posts_are_excluded_from_homepage_education(): void
    {
        $this->seed(CmsSeeder::class);

        Post::factory()->create([
            'title' => 'Visible Guide',
            'is_published' => true,
            'published_at' => now()->subHour(),
        ]);
        Post::factory()->draft()->create([
            'title' => 'Hidden Draft Guide',
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Visible Guide', false);
        $response->assertDontSee('Hidden Draft Guide', false);
    }
}
