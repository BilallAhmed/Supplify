@if (($dailyPicks ?? collect())->isNotEmpty())
<section id="daily-essentials" class="daily-picks" aria-labelledby="daily-picks-heading" data-daily-picks>
    <x-container class="daily-picks__container">
        <div class="daily-picks__layout">
            <div class="daily-picks__selection">
                <h2 id="daily-picks-heading">{{ $section->title }}<br>{{ $section->config['title_second'] ?? '' }}</h2>
                <div class="daily-picks__carousel" role="region" aria-roledescription="carousel" aria-label="Daily wellness products">
                    <button type="button" class="daily-picks__arrow daily-picks__arrow--prev" data-pick-prev aria-label="Previous daily essential"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m14 6-6 6 6 6" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                    <div class="daily-picks__viewport" data-pick-viewport tabindex="0" aria-label="Use left and right arrow keys to browse products">
                        @foreach ($dailyPicks as $product)
                            <div class="daily-picks__slide {{ $loop->first ? 'is-active' : '' }}" data-pick-slide data-product-name="{{ $product->name }}" role="group" aria-roledescription="slide" aria-label="{{ $loop->iteration }} of {{ $loop->count }}" aria-hidden="{{ $loop->first ? 'false' : 'true' }}" @if (!$loop->first) inert @endif>
                                @include('storefront.home.partials.product_card', ['product' => $product])
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="daily-picks__arrow daily-picks__arrow--next" data-pick-next aria-label="Next daily essential"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m10 6 6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                </div>
                <div class="daily-picks__dots" aria-label="Choose a daily essential">
                    @foreach ($dailyPicks as $product)
                        <button type="button" data-pick-dot aria-label="Show {{ $product->name }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}"><span></span></button>
                    @endforeach
                </div>
                <p class="sr-only" aria-live="polite" aria-atomic="true" data-pick-status></p>
            </div>
            <figure class="daily-picks__visual">
                <img src="{{ \App\Support\HomepageLayout::image($section->image_path) }}" alt="{{ $section->config['image_alt'] ?? '' }}" width="900" height="1125" loading="lazy" decoding="async">
            </figure>
        </div>
    </x-container>
</section>
@endif
