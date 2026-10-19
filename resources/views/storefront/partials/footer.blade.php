<footer class="mt-auto border-t border-line bg-surface-deep text-white" role="contentinfo">
    <x-container class="ui-section !py-12 lg:!py-16">
        <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-4 lg:gap-8">
            <div class="lg:col-span-1">
                <a href="{{ route('home') }}" class="inline-block" aria-label="{{ config('app.name') }} home">
                    <img
                        src="{{ asset('images/supplify-logo-dark.webp') }}"
                        alt="{{ config('app.name') }}"
                        class="site-footer__logo"
                        width="200"
                        height="44"
                        decoding="async"
                    >
                </a>
                <p class="mt-3 max-w-xs text-sm leading-relaxed text-white/70">
                    Science-led supplements for everyday performance. Transparent formulas. US-focused fulfillment.
                </p>
            </div>

            <div>
                <h2 class="text-xs font-semibold tracking-[0.16em] text-white/50 uppercase">Shop</h2>
                <ul class="mt-4 space-y-2.5 text-sm text-white/80">
                    <li><a href="{{ route('shop.index') }}" class="hover:text-white">All products</a></li>
                    <li><a href="{{ route('home') }}#goals" class="hover:text-white">Shop by goal</a></li>
                </ul>
            </div>

            <div>
                <h2 class="text-xs font-semibold tracking-[0.16em] text-white/50 uppercase">Support</h2>
                <ul class="mt-4 space-y-2.5 text-sm text-white/80">
                    <li><a href="{{ route('pages.show', 'contact') }}" class="hover:text-white">Contact</a></li>
                    <li><a href="{{ route('pages.show', 'shipping') }}" class="hover:text-white">Shipping</a></li>
                    <li><a href="{{ route('pages.show', 'returns') }}" class="hover:text-white">Returns</a></li>
                    <li><a href="{{ route('pages.show', 'faq') }}" class="hover:text-white">FAQ</a></li>
                </ul>
            </div>

            <div>
                <h2 class="text-xs font-semibold tracking-[0.16em] text-white/50 uppercase">Legal</h2>
                <ul class="mt-4 space-y-2.5 text-sm text-white/80">
                    <li><a href="{{ route('pages.show', 'privacy') }}" class="hover:text-white">Privacy</a></li>
                    <li><a href="{{ route('pages.show', 'terms') }}" class="hover:text-white">Terms</a></li>
                    <li><a href="{{ route('pages.show', 'about') }}" class="hover:text-white">About</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-12 flex flex-col gap-3 border-t border-white/10 pt-6 text-xs text-white/50 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p>These statements have not been evaluated by the FDA. Products are not intended to diagnose, treat, cure, or prevent any disease.</p>
        </div>
    </x-container>
</footer>
