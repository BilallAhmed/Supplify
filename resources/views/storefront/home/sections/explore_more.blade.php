@if (($exploreProducts ?? collect())->isNotEmpty())
<section
    id="explore-more"
    class="home-explore home-band home-band--surface ui-section"
    aria-labelledby="explore-heading"
>
    <x-container>
        <div class="home-explore__head">
            <x-section-heading
                id="explore-heading"
                :eyebrow="$section->subtitle"
                :title="$section->title ?: 'Explore More Formulas'"
                :description="$section->body === 'More published products from the Supplify catalog, ordered by publication date.' ? 'Discover more essentials for your everyday routine.' : $section->body"
            />

            <x-view-all-products />
        </div>

        <div class="home-explore__grid mt-10">
            @foreach ($exploreProducts as $product)
                <x-product-tile :product="$product" variant="grid" />
            @endforeach
        </div>
    </x-container>
</section>
@endif
