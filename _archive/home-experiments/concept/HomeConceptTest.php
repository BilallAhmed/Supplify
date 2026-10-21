<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeConceptTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_concept_is_isolated_from_live_homepage(): void
    {
        Product::factory()->create([
            'sku' => 'SUP-MAGNESIUM-250MG-90',
            'name' => 'Magnesium 250mg',
            'slug' => 'magnesium-250mg-90-tablets',
            'is_active' => true,
            'short_description' => 'Supplify Magnesium 250mg dietary supplement, 90 tablets.',
            'supplement_facts' => "Serving Size: 1 tablet\nMagnesium: 250 mg",
            'directions' => 'Take 1 tablet daily with a meal.',
        ]);

        Product::factory()->create([
            'sku' => 'SUP-COQ10-200MG-120',
            'name' => 'CoQ10 200mg',
            'slug' => 'coq10-200mg-120-softgels',
            'is_active' => true,
            'short_description' => 'Supplify CoQ10 200mg dietary supplement, 120 softgels.',
        ]);

        Product::factory()->create([
            'sku' => 'SUP-VITAMIN-B50-COMPLEX-120',
            'name' => 'Vitamin B-50 Complex',
            'slug' => 'vitamin-b-50-complex-120-tablets',
            'is_active' => true,
            'short_description' => 'Supplify Vitamin B-50 Complex dietary supplement, 120 tablets.',
        ]);

        Product::factory()->create([
            'sku' => 'SUP-BERBERINE-500MG-120',
            'name' => 'Berberine 500mg',
            'slug' => 'berberine-500mg-120-vegcaps',
            'is_active' => true,
            'short_description' => 'Supplify Berberine 500mg dietary supplement, 120 vegetable capsules.',
        ]);

        $this->get(route('home.concept'))
            ->assertOk()
            ->assertSee('data-home-concept', false)
            ->assertSee('Trusted nutrition for everyday routines', false)
            ->assertSee('Explore Products', false)
            ->assertSee('Magnesium 250mg', false)
            ->assertSee('CoQ10 200mg', false)
            ->assertSee('Vitamin B-50 Complex', false)
            ->assertSee('Berberine 500mg', false)
            ->assertSee('noindex', false)
            ->assertSee(route('shop.index'), false);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('data-home-concept', false)
            ->assertDontSee('Phase 1 homepage concept', false);
    }
}
