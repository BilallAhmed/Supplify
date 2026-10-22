<section class="home-newsletter home-band" aria-labelledby="newsletter-heading">
    <x-container class="home-newsletter__inner">
        <div class="home-newsletter__copy">
            <p class="home-newsletter__eyebrow">{{ $section->subtitle ?: 'Newsletter' }}</p>
            <h2 id="newsletter-heading" class="home-newsletter__title font-display">
                {{ $section->title ?: 'Stay in the loop' }}
            </h2>
            @if ($section->body)
                <p class="home-newsletter__lede">{{ $section->body }}</p>
            @endif
        </div>

        <form method="POST" action="{{ route('newsletter.store') }}" class="home-newsletter__form">
            @csrf
            <label for="newsletter-email" class="home-newsletter__label">{{ $section->config['email_label'] ?? 'Email' }}</label>
            <div class="home-newsletter__row">
                <input
                    id="newsletter-email"
                    type="email"
                    name="email"
                    required
                    value="{{ old('email') }}"
                    placeholder="{{ $section->config['email_placeholder'] ?? 'you@example.com' }}"
                    class="home-newsletter__input"
                    autocomplete="email"
                >
                <button type="submit" class="home-newsletter__submit">
                    {{ $section->cta_label ?: 'Subscribe' }}
                </button>
            </div>
            @error('email')
                <p class="home-newsletter__error" role="alert">{{ $message }}</p>
            @enderror
        </form>
    </x-container>
</section>
