<section class="home-band home-band--deep ui-section" aria-labelledby="newsletter-heading">
    <x-container class="grid items-center gap-8 lg:grid-cols-2">
        <div data-reveal="up">
            @if ($section->subtitle)
                <p class="mb-2 text-xs font-semibold tracking-[0.18em] text-white/50 uppercase">{{ $section->subtitle }}</p>
            @endif
            <h2 id="newsletter-heading" class="font-display text-3xl font-bold tracking-tight sm:text-4xl">{{ $section->title }}</h2>
            @if ($section->body)
                <p class="mt-3 max-w-md text-white/70">{{ $section->body }}</p>
            @endif
        </div>

        <form method="POST" action="{{ route('newsletter.store') }}" class="rounded-[var(--radius-panel)] border border-white/10 bg-white/5 p-5 backdrop-blur-sm" data-reveal="scale">
            @csrf
            <label for="newsletter-email" class="mb-1.5 block text-sm font-medium text-white/90">Email</label>
            <div class="flex flex-col gap-3 sm:flex-row">
                <input
                    id="newsletter-email"
                    type="email"
                    name="email"
                    required
                    value="{{ old('email') }}"
                    placeholder="you@example.com"
                    class="ui-input flex-1 border-white/20 bg-white text-ink"
                >
                <x-button type="submit" variant="accent" size="md" data-magnetic="12">
                    {{ $section->cta_label ?: 'Subscribe' }}
                </x-button>
            </div>
            @error('email')
                <p class="mt-2 text-xs text-red-200">{{ $message }}</p>
            @enderror
        </form>
    </x-container>
</section>
