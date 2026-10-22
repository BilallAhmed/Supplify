@php
    $goalPhotos = [
        'energy-focus' => 'images/homepage/goals/energy-focus.webp',
        'sleep-recovery' => 'images/homepage/goals/sleep-recovery.webp',
        'immunity-defense' => 'images/homepage/goals/immunity-defense.webp',
        'gut-digestion' => 'images/homepage/goals/gut-digestion.webp',
    ];
@endphp
<section id="goals" class="home-goals home-goals--gallery" aria-labelledby="goals-heading" data-goal-directory>
    <x-container class="goal-gallery__container">
        <header class="goal-gallery__header">
            <h2 class="goal-gallery__heading" id="goals-heading">{{ $section->title ?: 'Shop by Goals' }}</h2>
        </header>
        <div class="goal-gallery__grid">
            @forelse ($goalCategories as $category)
                @php
                    $photoPath = $goalPhotos[$category->slug] ?? null;
                    $photo = $category->image_path ? \App\Support\HomepageLayout::image($category->image_path) : null;
                    if (! $photo && $photoPath && is_file(public_path($photoPath))) {
                        $photo = asset($photoPath).'?v='.filemtime(public_path($photoPath));
                    }
                @endphp
                <article class="goal-gallery__card">
                    <a class="goal-gallery__content" href="{{ route('shop.category', $category->slug) }}">
                    <span class="goal-gallery__image">
                        @if ($photo)
                            <img src="{{ $photo }}" alt="" width="720" height="1008" loading="lazy" decoding="async">
                        @else
                            <span class="goal-gallery__fallback" aria-hidden="true"><x-goal-icon :name="$category->resolvedIconKey()" /></span>
                        @endif
                    </span>
                    <h3 class="goal-gallery__title">{{ $category->name }}</h3>
                    </a>
                    <p class="goal-gallery__description">{{ $category->description ?: 'Find your everyday essentials.' }}</p>
                    <a class="goal-gallery__button" href="{{ route('shop.category', $category->slug) }}">
                        <span>{{ $section->config['button_label'] ?? 'Shop Now' }}<span class="sr-only">: {{ $category->name }}</span></span>
                        <svg class="goal-gallery__arrow" width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                </article>
            @empty
                <p class="goal-gallery__empty">Categories will be available soon.</p>
            @endforelse
        </div>
    </x-container>
</section>
