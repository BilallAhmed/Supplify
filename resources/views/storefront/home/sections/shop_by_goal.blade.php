<section id="goals" class="home-goals home-goals--cards" aria-labelledby="goals-heading" data-goal-directory>
    <x-container>
        <header class="goal-cards__header">
            <div>
                <p class="editorial-kicker">{{ $section->subtitle ?: 'Find your everyday essentials' }}</p>
                <h2 id="goals-heading">{{ $section->title ?: 'Shop by goal' }}</h2>
                @if ($section->body)<p class="editorial-intro">{{ $section->body }}</p>@endif
            </div>
            <x-view-all-products />
        </header>
        <div class="goal-cards__grid">
            @forelse ($goalCategories as $category)
                <a class="goal-cards__card" href="{{ route('shop.category', $category->slug) }}">
                    <span class="goal-cards__icon" aria-hidden="true">
                        @if ($category->customIconUrl())
                            <img src="{{ $category->customIconUrl() }}" alt="" width="32" height="32" loading="lazy" decoding="async">
                        @else
                            <x-goal-icon :name="$category->resolvedIconKey()" />
                        @endif
                    </span>
                    <h3>{{ $category->name }}</h3>
                    @if ($category->description)<p>{{ $category->description }}</p>@endif
                    <span class="goal-cards__browse" aria-hidden="true">Explore formulas <svg width="18" height="18" viewBox="0 0 20 20" fill="none"><path d="M4 10h12M11 5l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                </a>
            @empty
                <p class="goal-cards__empty">Categories will be available soon.</p>
            @endforelse
        </div>
    </x-container>
</section>
