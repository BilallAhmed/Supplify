<?php

namespace Tests\Feature;

use App\Models\HomepageSection;
use App\Models\Product;
use App\Models\User;
use App\Support\HomepageLayout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageEditorTest extends TestCase
{
    use RefreshDatabase;

    private function payload(HomepageSection $section): array
    {
        $values = HomepageLayout::values($section);
        $data = ['is_enabled' => true];
        foreach (HomepageLayout::fields($section->key) as $field) {
            data_set($data, $field['path'], data_get($values, $field['path']));
        }

        return $data;
    }

    public function test_editor_has_only_current_sections_and_no_json_configuration(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.homepage.edit'));
        $response->assertOk()->assertSee('Wellness banners')->assertSee('Your Daily Essentials')->assertSee('Why Choose Us')->assertDontSee('config_json')->assertDontSee('Trust / benefits')->assertDontSee('Explore More Formulas');
    }

    public function test_hero_copy_and_selected_media_reach_the_storefront_without_overwriting_other_slides(): void
    {
        $hero = HomepageSection::where('key', 'hero')->firstOrFail();
        $data = $this->payload($hero);
        $second = $data['config']['slides'][1]['title'];
        $data['config']['slides'][0]['title'] = 'A clear new heading';
        $data['config']['slides'][0]['scene'] = 'images/homepage/featured-products.webp';
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.homepage.update', $hero), $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame($second, $hero->fresh()->config['slides'][1]['title']);
        $this->get(route('home'))->assertOk()->assertSee('A clear new heading')->assertSee('images/homepage/featured-products.webp');
    }

    public function test_new_sections_are_independent_and_saving_why_choose_us_updates_visible_content(): void
    {
        $section = HomepageSection::where('key', 'why_choose_us')->firstOrFail();
        $data = $this->payload($section);
        $data['config']['reasons'][0]['title'] = 'Carefully reviewed information';
        $data['config']['strip_words'][0] = 'Clear choices';
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.homepage.update', $section), $data)->assertSessionHasNoErrors();
        HomepageSection::where('key', 'story')->update(['is_enabled' => false]);
        $this->get(route('home'))->assertOk()->assertSee('Carefully reviewed information')->assertSee('Clear choices')->assertSee('data-why-supplify', false)->assertDontSee('data-home-approach', false);
    }

    public function test_banner_selections_reject_duplicate_and_unpublished_products(): void
    {
        $section = HomepageSection::where('key', 'routine_campaign')->firstOrFail();
        $a = Product::factory()->create(['is_active' => true, 'published_at' => now()->subHour(), 'catalog_visibility' => 'visible']);
        $b = Product::factory()->create(['is_active' => true, 'published_at' => now()->subHour(), 'catalog_visibility' => 'visible']);
        $draft = Product::factory()->create(['published_at' => null]);
        $data = $this->payload($section);
        $data['config']['left']['product_ids'] = [$a->id, $a->id, $draft->id];
        $data['config']['right']['product_ids'] = [$a->id, $b->id];
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.homepage.update', $section), $data)->assertSessionHasErrors(['config.left.product_ids', 'config.left.product_ids.2']);
    }

    public function test_banner_products_and_copy_render_from_saved_configuration(): void
    {
        $section = HomepageSection::where('key', 'routine_campaign')->firstOrFail();
        $products = Product::factory()->count(3)->create(['is_active' => true, 'published_at' => now()->subHour(), 'catalog_visibility' => 'visible']);
        $data = $this->payload($section);
        $data['config']['left']['product_ids'] = $products->pluck('id')->all();
        $data['config']['right']['product_ids'] = $products->take(2)->pluck('id')->all();
        $data['config']['right']['title'] = 'Your wellness shelf';
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.homepage.update', $section), $data)->assertSessionHasNoErrors();
        $response = $this->get(route('home'))->assertOk()->assertSee('Your wellness shelf');
        foreach ($products as $product) {
            $response->assertSee(route('products.show', $product->slug));
        }
    }

    public function test_retired_sections_cannot_be_updated_and_customers_cannot_edit_homepage(): void
    {
        $retired = HomepageSection::firstOrCreate(['key' => 'trust']);
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.homepage.update', $retired), ['is_enabled' => true])->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('admin.homepage.edit'))->assertForbidden();
    }

    public function test_invalid_destinations_and_missing_media_are_rejected(): void
    {
        $section = HomepageSection::where('key', 'why_choose_us')->firstOrFail();
        $data = $this->payload($section);
        $data['cta_url'] = 'javascript:alert(1)';
        $data['image_path'] = 'images/not-an-image.webp';
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.homepage.update',$section), $data)->assertSessionHasErrors(['cta_url', 'image_path']);
    }
}
