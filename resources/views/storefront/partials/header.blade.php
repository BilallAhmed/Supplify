@php
    $navLinks = [
        ['label' => 'Home', 'href' => route('home')],
        ['label' => 'All products', 'href' => route('shop.index')],
        ['label' => 'Best Seller', 'href' => route('shop.index', ['collection' => 'best-sellers'])],
        ['label' => 'Discount & Offers', 'href' => route('shop.index', ['on_sale' => '1'])],
        ['label' => 'Trending products', 'href' => route('shop.index', ['collection' => 'trending'])],
        ['label' => 'Blogs', 'href' => route('blog.index')],
        ['label' => 'About Us', 'href' => route('pages.show', 'about')],
        ['label' => 'Contact', 'href' => route('pages.show', 'contact')],
    ];
@endphp

<header
    class="site-header sticky top-0 z-40{{ request()->routeIs('home') ? ' is-over-hero is-hero-dark' : '' }}"
    x-data="siteHeader(@js(route('search')))"
    :class="{ 'is-scrolled': scrolled, 'is-open': mobileOpen, 'is-searching': searchOpen }"
    @keydown.escape.window="
        if (searchOpen) { closeSearch(); }
        else { mobileOpen = false; }
    "
>
    <x-container class="site-header__container">
        <div class="site-header__bar">
            <a href="{{ route('home') }}" class="group flex min-w-0 items-center" aria-label="{{ config('app.name') }} home">
                <img
                    src="{{ asset('images/supplify-logo-dark.webp') }}"
                    alt="{{ config('app.name') }}"
                    class="site-header__logo site-header__logo--on-dark"
                    width="220"
                    height="48"
                    decoding="async"
                >
                <img
                    src="{{ asset('images/supplify-logo-light.webp') }}"
                    alt=""
                    class="site-header__logo site-header__logo--on-light"
                    width="220"
                    height="48"
                    decoding="async"
                    aria-hidden="true"
                >
            </a>

            <nav
                class="site-header__nav"
                aria-label="Primary"
                :aria-hidden="searchOpen.toString()"
                :inert="searchOpen"
            >
                @foreach ($navLinks as $link)
                    <a href="{{ $link['href'] }}" class="site-header__link">
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="flex min-w-0 flex-1 items-center justify-end gap-1.5 sm:gap-2 lg:flex-none">
                <div
                    class="site-header__search"
                    :class="{ 'is-open': searchOpen }"
                    @click.outside="if (searchOpen) closeSearch()"
                >
                    <button
                        type="button"
                        class="site-header__icon-btn site-header__search-toggle"
                        @click="openSearch()"
                        aria-label="Open search"
                        data-tooltip="Search"
                        :aria-expanded="searchOpen.toString()"
                    >
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"/>
                            <path d="m20 20-3.5-3.5" stroke-linecap="round"/>
                        </svg>
                    </button>

                    <div class="site-header__search-shell" :aria-hidden="(!searchOpen).toString()">
                        <label class="sr-only" for="header-search-input">Search</label>
                        <span class="site-header__search-glyph" aria-hidden="true">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                                <circle cx="11" cy="11" r="7"/>
                                <path d="m20 20-3.5-3.5" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <input
                            id="header-search-input"
                            x-ref="searchInput"
                            type="search"
                            class="site-header__search-input"
                            placeholder="Search products & pages"
                            autocomplete="off"
                            autocorrect="off"
                            spellcheck="false"
                            enterkeyhint="search"
                            tabindex="-1"
                            :tabindex="searchOpen ? 0 : -1"
                            x-model="query"
                            @input="onQueryInput()"
                            @keydown.enter.prevent
                        >
                        <button
                            type="button"
                            class="site-header__search-close"
                            @click="closeSearch()"
                            aria-label="Close search"
                            tabindex="-1"
                            :tabindex="searchOpen ? 0 : -1"
                        >
                            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                            </svg>
                        </button>

                        <div
                            class="site-header__search-panel"
                            x-show="showPanel"
                            x-cloak
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 translate-y-1"
                            role="listbox"
                            aria-label="Search results"
                        >
                            <div class="site-header__search-panel-inner">
                                <template x-if="loading && results.length === 0">
                                    <p class="site-header__search-status">Searching…</p>
                                </template>

                                <template x-if="!loading && searched && results.length === 0">
                                    <p class="site-header__search-status">No results for “<span x-text="query.trim()"></span>”</p>
                                </template>

                                <template x-if="productResults.length">
                                    <div class="site-header__search-group">
                                        <p class="site-header__search-group-label">Products</p>
                                        <template x-for="item in productResults" :key="'p-' + item.url">
                                            <a :href="item.url" class="site-header__search-item" role="option">
                                                <span class="site-header__search-thumb">
                                                    <img :src="item.image" alt="" loading="lazy" width="40" height="40">
                                                </span>
                                                <span class="site-header__search-copy">
                                                    <span class="site-header__search-title" x-text="item.title"></span>
                                                    <span class="site-header__search-meta" x-text="item.meta"></span>
                                                </span>
                                            </a>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="articleResults.length">
                                    <div class="site-header__search-group">
                                        <p class="site-header__search-group-label">Articles</p>
                                        <template x-for="item in articleResults" :key="'a-' + item.url">
                                            <a :href="item.url" class="site-header__search-item" role="option">
                                                <span class="site-header__search-thumb" :class="!item.image && 'site-header__search-thumb--icon'" aria-hidden="true">
                                                    <img x-show="item.image" :src="item.image || ''" alt="" loading="lazy" width="40" height="40">
                                                    <svg x-show="!item.image" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                        <path d="M7 3.5h7.5L19 8v12.5a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-16a1 1 0 0 1 1-1Z" stroke-linejoin="round"/>
                                                        <path d="M14.5 3.5V8H19" stroke-linejoin="round"/>
                                                    </svg>
                                                </span>
                                                <span class="site-header__search-copy">
                                                    <span class="site-header__search-title" x-text="item.title"></span>
                                                    <span class="site-header__search-meta" x-text="item.meta"></span>
                                                </span>
                                            </a>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="pageResults.length">
                                    <div class="site-header__search-group">
                                        <p class="site-header__search-group-label">Pages</p>
                                        <template x-for="item in pageResults" :key="'g-' + item.url">
                                            <a :href="item.url" class="site-header__search-item" role="option">
                                                <span class="site-header__search-thumb site-header__search-thumb--icon" aria-hidden="true">
                                                    <img x-show="item.image" :src="item.image || ''" alt="" loading="lazy" width="40" height="40">
                                                    <svg x-show="!item.image" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                        <path d="M7 3.5h7.5L19 8v12.5a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-16a1 1 0 0 1 1-1Z" stroke-linejoin="round"/>
                                                        <path d="M14.5 3.5V8H19" stroke-linejoin="round"/>
                                                    </svg>
                                                </span>
                                                <span class="site-header__search-copy">
                                                    <span class="site-header__search-title" x-text="item.title"></span>
                                                    <span class="site-header__search-meta" x-text="item.meta"></span>
                                                </span>
                                            </a>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="{{ route('cart.index') }}" class="site-header__icon-btn relative" aria-label="Cart{{ ($cartCount ?? 0) > 0 ? ' ('.$cartCount.' items)' : '' }}" data-tooltip="Cart">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path d="M3 5h2l1.6 9.2a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 2-1.6L20 8H7" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="10" cy="20" r="1.2" fill="currentColor" stroke="none"/>
                        <circle cx="17" cy="20" r="1.2" fill="currentColor" stroke="none"/>
                    </svg>
                    @if (($cartCount ?? 0) > 0)
                        <span class="site-header__badge">{{ $cartCount > 99 ? '99+' : $cartCount }}</span>
                    @endif
                </a>

                @auth
                    <a href="{{ route('account.dashboard') }}" class="site-header__icon-btn" aria-label="Account" data-tooltip="Account">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <circle cx="12" cy="8" r="3.25"/>
                            <path d="M5.5 19.5c1.6-3.2 4-4.75 6.5-4.75s4.9 1.55 6.5 4.75" stroke-linecap="round"/>
                        </svg>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                        @csrf
                        <button type="submit" class="site-header__text-btn" data-tooltip="Sign out">Sign out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="site-header__icon-btn" aria-label="Sign in" data-tooltip="Sign in">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <circle cx="12" cy="8" r="3.25"/>
                            <path d="M5.5 19.5c1.6-3.2 4-4.75 6.5-4.75s4.9 1.55 6.5 4.75" stroke-linecap="round"/>
                        </svg>
                    </a>
                    <a href="{{ route('register') }}" class="site-header__join hidden sm:inline-flex" x-show="!searchOpen">
                        Join
                    </a>
                @endauth

                <button
                    type="button"
                    class="site-header__icon-btn lg:hidden"
                    @click="mobileOpen = !mobileOpen; if (mobileOpen) closeSearch()"
                    :aria-expanded="mobileOpen.toString()"
                    aria-controls="mobile-nav"
                    aria-label="Toggle menu"
                >
                    <svg x-show="!mobileOpen" class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M2 5.75A.75.75 0 0 1 2.75 5h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 5.75Zm0 4.25a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 10Zm0 4.25a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H2.75a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd"/>
                    </svg>
                    <svg x-show="mobileOpen" x-cloak class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                    </svg>
                </button>
            </div>
        </div>
    </x-container>

    <div
        id="mobile-nav"
        x-show="mobileOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="site-header__mobile lg:hidden"
    >
        <x-container class="space-y-1 py-4">
            @foreach ($navLinks as $link)
                <a
                    href="{{ $link['href'] }}"
                    class="site-header__mobile-link"
                    @click="mobileOpen = false"
                >
                    {{ $link['label'] }}
                </a>
            @endforeach

            <div class="space-y-2 border-t border-white/10 pt-3">
                @auth
                    <a href="{{ route('account.dashboard') }}" class="site-header__mobile-link" @click="mobileOpen = false">Account</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="site-header__mobile-link w-full text-left">Sign out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="site-header__mobile-link" @click="mobileOpen = false">Sign in</a>
                    <a href="{{ route('register') }}" class="site-header__mobile-link site-header__mobile-link--accent" @click="mobileOpen = false">Create account</a>
                @endauth
            </div>
        </x-container>
    </div>
</header>
