<section id="goals" class="home-goals home-band home-band--mist ui-section border-t border-line/40" aria-labelledby="goals-heading">
    <x-container>
        <div data-reveal="up">
            <x-section-heading
                id="goals-heading"
                :eyebrow="$section->subtitle"
                :title="$section->title ?: 'Shop by goal'"
                :description="$section->body"
            />
        </div>

        <div class="home-goals__grid mt-10">
            @forelse ($goalCategories as $i => $category)
                <a
                    href="{{ route('shop.category', $category->slug) }}"
                    class="home-goals__card"
                    data-reveal="{{ $i % 2 === 0 ? 'left' : 'right' }}"
                >
                    <span class="home-goals__icon" aria-hidden="true">
                        @if ($category->customIconUrl())
                            <img
                                src="{{ $category->customIconUrl() }}"
                                alt=""
                                class="home-goals__icon-img"
                                width="32"
                                height="32"
                                loading="lazy"
                            >
                        @else
                            <x-goal-icon :name="$category->resolvedIconKey()" class="home-goals__icon-svg" />
                        @endif
                    </span>
                    <span class="home-goals__copy">
                        <span class="home-goals__title">{{ $category->name }}</span>
                        @if (filled($category->description))
                            <span class="home-goals__desc">{{ $category->description }}</span>
                        @endif
                    </span>
                    <span class="home-goals__cta">Shop →</span>
                </a>
            @empty
                <div class="home-goals__empty">
                    <p class="text-sm text-ink-muted">Add active categories to power this section.</p>
                </div>
            @endforelse
        </div>
    </x-container>
</section>
