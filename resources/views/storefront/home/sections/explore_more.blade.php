@if (($exploreProducts ?? collect())->isNotEmpty())
<section
    id="explore-more"
    class="home-explore home-band home-band--surface ui-section"
    aria-labelledby="explore-heading"
>
    <x-container>
        <div class="home-explore__head" data-reveal="up">
            <x-section-heading
                id="explore-heading"
                :eyebrow="$section->subtitle"
                :title="$section->title ?: 'Explore More Formulas'"
                :description="$section->body"
            />

            @if ($section->cta_label)
                <a href="{{ url($section->cta_url ?: '/shop') }}" class="home-explore__all">
                    {{ $section->cta_label }}
                </a>
            @endif
        </div>

        <div class="home-explore__grid mt-10">
            @foreach ($exploreProducts as $product)
                <x-product-tile :product="$product" variant="grid" data-reveal="up" />
            @endforeach
        </div>
    </x-container>
</section>
@endif
