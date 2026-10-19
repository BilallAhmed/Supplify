<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::query()->where('email', 'admin@example.com')->value('id');

        $categories = [
            ['name' => 'Routines', 'slug' => 'routines', 'sort_order' => 10],
            ['name' => 'Ingredients', 'slug' => 'ingredients', 'sort_order' => 20],
            ['name' => 'How we evaluate', 'slug' => 'how-we-evaluate', 'sort_order' => 30],
        ];

        foreach ($categories as $category) {
            PostCategory::query()->updateOrCreate(
                ['slug' => $category['slug']],
                $category + ['description' => null]
            );
        }

        $posts = [
            [
                'title' => 'How to read a supplement label without the fog',
                'slug' => 'how-to-read-a-supplement-label',
                'category' => 'how-we-evaluate',
                'excerpt' => 'A calmer way to scan serving size, forms, and filler language before you buy.',
                'body' => "Start with the serving size — not the front of the bottle.\n\nLook for the actual form of each ingredient (for example, magnesium glycinate vs oxide) and whether amounts are listed clearly.\n\nProprietary blends can hide dosing. Prefer labels that show milligrams per ingredient when it matters for your routine.\n\nFinally, check directions and warnings. Clear use instructions are part of quality, not an afterthought.",
                'tags' => 'labels, transparency',
                'image' => null,
            ],
            [
                'title' => 'Building an evening wind-down stack that actually sticks',
                'slug' => 'evening-wind-down-stack',
                'category' => 'routines',
                'excerpt' => 'Keep it simple: timing, consistency, and formulas that match a real weeknight.',
                'body' => "Most people do not need a ten-product nighttime ritual.\n\nPick one primary support (often magnesium or a calm-focused formula), take it at a consistent time, and give it a few weeks before judging.\n\nPair the supplement with the basics that matter more: dimmer lights, a fixed bedtime window, and fewer late caffeine hits.\n\nIf you add a second product later, do it one change at a time so you can tell what is helping.",
                'tags' => 'sleep, magnesium, routines',
                'image' => null,
            ],
            [
                'title' => 'Magnesium forms, explained in plain English',
                'slug' => 'magnesium-forms-explained',
                'category' => 'ingredients',
                'excerpt' => 'Glycinate, citrate, oxide — why the form on the label matters more than the headline milligrams.',
                'body' => "Not all magnesium is interchangeable.\n\nGlycinate is often chosen for evening routines because it is typically well tolerated.\n\nCitrate shows up in many daily formulas and may have a different digestive feel for some people.\n\nOxide is common and inexpensive, but absorption and comfort vary.\n\nMatch the form to your goal and tolerance, then look for a transparent dose — not just a large number on the front panel.",
                'tags' => 'magnesium, ingredients',
                'image' => null,
            ],
            [
                'title' => 'Daily greens: what “coverage” should mean',
                'slug' => 'daily-greens-coverage',
                'category' => 'ingredients',
                'excerpt' => 'Think of greens as a support layer — not a replacement for meals you already know how to eat.',
                'body' => "A greens formula can help fill gaps on busy days, but it is not a free pass to skip vegetables.\n\nLook for clear ingredient lists, realistic serving sizes, and flavor systems that do not bury the formula in sugar.\n\nIf the label is mostly proprietary blend names, treat that as a signal to ask better questions.\n\nUse greens as a consistent habit alongside food, hydration, and protein — not instead of them.",
                'tags' => 'greens, daily',
                'image' => null,
            ],
        ];

        foreach ($posts as $entry) {
            $categoryId = PostCategory::query()->where('slug', $entry['category'])->value('id');

            $post = Post::query()->updateOrCreate(
                ['slug' => $entry['slug']],
                [
                    'title' => $entry['title'],
                    'excerpt' => $entry['excerpt'],
                    'body' => $entry['body'],
                    'post_category_id' => $categoryId,
                    'author_id' => $adminId,
                    'featured_image' => $entry['image'],
                    'is_published' => true,
                    'published_at' => now()->subDays(fake()->numberBetween(1, 20)),
                    'seo_title' => $entry['title'].' | Supplify',
                    'seo_description' => $entry['excerpt'],
                ]
            );

            $tagIds = collect(explode(',', $entry['tags']))
                ->map(fn (string $name) => trim($name))
                ->filter()
                ->map(fn (string $name) => Tag::query()->firstOrCreate(
                    ['slug' => \Illuminate\Support\Str::slug($name)],
                    ['name' => $name]
                )->id);

            $post->tags()->sync($tagIds->all());
        }
    }
}
