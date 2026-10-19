<section class="home-featured" aria-labelledby="featured-heading" data-home-featured>
    <x-container>
        <div class="home-featured__head" data-reveal="left">
            <x-section-heading
                id="featured-heading"
                :eyebrow="$section->subtitle"
                :title="$section->title ?: 'Featured formulas'"
                :description="$section->body"
            />

            @if ($featuredProducts->isNotEmpty())
                <div class="home-featured__controls">
                    <a
                        href="{{ url($section->cta_url ?: '/shop') }}"
                        class="home-featured__all"
                    >{{ $section->cta_label ?: 'View All Products' }}</a>
                    <div class="home-featured__nav" role="group" aria-label="Featured products controls">
                        <button type="button" class="home-featured__arrow" data-featured-prev aria-label="Previous products">
                            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1-.02 1.06L8.832 10l3.938 3.71a.75.75 0 1 1-1.04 1.08l-4.5-4.25a.75.75 0 0 1 0-1.08l4.5-4.25a.75.75 0 0 1 1.06.02Z" clip-rule="evenodd"/></svg>
                        </button>
                        <button type="button" class="home-featured__arrow" data-featured-next aria-label="Next products">
                            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd"/></svg>
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </x-container>

    @if ($featuredProducts->isEmpty())
        <x-container>
            <p class="mt-8 text-sm text-ink-muted">Mark products as featured in admin to show them here.</p>
        </x-container>
    @else
        <div class="home-featured__viewport" data-featured-viewport tabindex="0" aria-label="Featured products carousel">
            <div class="home-featured__track" data-featured-track>
                @foreach ($featuredProducts as $i => $product)
                    <x-product-tile
                        :product="$product"
                        variant="featured"
                        class="home-featured__card {{ $i === 0 ? 'is-active' : '' }}"
                    />
                @endforeach
            </div>
        </div>
    @endif
</section>
