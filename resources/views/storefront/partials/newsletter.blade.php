@php($newsletterSection = \App\Models\HomepageSection::query()->where('key', 'newsletter')->first())
<section class="site-newsletter" aria-labelledby="newsletter-heading">
    <x-container class="site-newsletter__inner">
        <div class="site-newsletter__copy">
            <p class="site-newsletter__eyebrow">{{ $newsletterSection?->subtitle ?: 'Newsletter' }}</p>
            <h2 id="newsletter-heading" class="site-newsletter__title font-display">
                {{ \Illuminate\Support\Str::title($newsletterSection?->title ?: 'Stay in the loop') }}
            </h2>
            @if ($newsletterSection?->body ?? true)
                <p class="site-newsletter__lede">{{ $newsletterSection?->body ?: 'New formulas, restocks, and practical guidance — no spam.' }}</p>
            @endif
        </div>

        <form method="POST" action="{{ route('newsletter.store') }}" class="site-newsletter__form">
            @csrf
            <label for="newsletter-email" class="site-newsletter__label">{{ $newsletterSection?->config['email_label'] ?? 'Email' }}</label>
            <div class="site-newsletter__row">
                <input
                    id="newsletter-email"
                    type="email"
                    name="email"
                    required
                    value="{{ old('email') }}"
                    placeholder="{{ $newsletterSection?->config['email_placeholder'] ?? 'you@example.com' }}"
                    class="site-newsletter__input"
                    autocomplete="email"
                >
                <button type="submit" class="site-newsletter__submit">
                    {{ $newsletterSection?->cta_label ?: 'Subscribe' }}
                </button>
            </div>
            @error('email', 'newsletter')
                <p class="site-newsletter__error" role="alert">{{ $message }}</p>
            @enderror
        </form>
    </x-container>
</section>
