@php
    $navGroups = [
        [
            'label' => 'Overview',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'dashboard'],
                ['label' => 'Analytics', 'route' => 'admin.analytics', 'icon' => 'analytics'],
            ],
        ],
        [
            'label' => 'Catalog',
            'items' => [
                ['label' => 'Products', 'route' => 'admin.products.index', 'icon' => 'products'],
                ['label' => 'Categories', 'route' => 'admin.categories.index', 'icon' => 'categories'],
                ['label' => 'Inventory', 'route' => 'admin.inventory.index', 'icon' => 'inventory'],
            ],
        ],
        [
            'label' => 'Commerce',
            'items' => [
                ['label' => 'Orders', 'route' => 'admin.orders.index', 'icon' => 'orders'],
                ['label' => 'Customers', 'route' => 'admin.customers.index', 'icon' => 'customers'],
                ['label' => 'Coupons', 'route' => 'admin.coupons.index', 'icon' => 'coupons'],
            ],
        ],
        [
            'label' => 'Content',
            'items' => [
                ['label' => 'Homepage', 'route' => 'admin.homepage.edit', 'icon' => 'homepage'],
                ['label' => 'Pages', 'route' => 'admin.pages.index', 'icon' => 'pages'],
                ['label' => 'Enquiries', 'route' => 'admin.contact-enquiries.index', 'icon' => 'enquiries'],
                ['label' => 'Blog', 'route' => 'admin.blog.index', 'icon' => 'blog'],
                ['label' => 'Media', 'route' => 'admin.media.index', 'icon' => 'media'],
            ],
        ],
        [
            'label' => 'System',
            'items' => [
                ['label' => 'Assistant', 'route' => 'admin.assistant.edit', 'icon' => 'settings', 'match' => 'admin.assistant.*'],
                ['label' => 'Settings', 'route' => 'admin.settings.edit', 'icon' => 'settings', 'match' => 'admin.settings.*'],
            ],
        ],
    ];
@endphp

<div class="admin-sidebar-backdrop lg:hidden" @click="mobileNav = false" aria-hidden="true"></div>

<aside id="admin-sidebar" class="admin-sidebar" aria-label="Admin navigation" :aria-expanded="(! collapsed).toString()">
    <div class="admin-sidebar__brand">
        <a href="{{ route('admin.dashboard') }}" class="admin-sidebar__brand-link min-w-0" aria-label="{{ config('app.name') }} admin">
            <img
                x-show="!collapsed || !window.matchMedia('(min-width: 1024px)').matches"
                src="{{ asset('images/supplify-logo-dark.webp') }}"
                alt="{{ config('app.name') }}"
                class="admin-sidebar__logo admin-sidebar__logo--full"
                width="160"
                height="36"
                decoding="async"
            >
            <img
                x-cloak
                x-show="collapsed && window.matchMedia('(min-width: 1024px)').matches"
                src="{{ asset('images/supplify-mark-dark.png') }}"
                alt=""
                class="admin-sidebar__logo admin-sidebar__logo--mark"
                width="36"
                height="36"
                decoding="async"
                aria-hidden="true"
            >
        </a>
    </div>

    <nav class="admin-sidebar__nav">
        @foreach ($navGroups as $group)
            <p class="admin-sidebar__group">{{ $group['label'] }}</p>
            <ul class="space-y-0.5">
                @foreach ($group['items'] as $item)
                    @php
                        $active = isset($item['match'])
                            ? request()->routeIs($item['match'])
                            : (
                                request()->routeIs($item['route'])
                                || request()->routeIs(str_replace('.index', '.*', $item['route']))
                                || request()->routeIs(str_replace('.edit', '.*', $item['route']))
                            );
                    @endphp
                    <li>
                        <a
                            href="{{ route($item['route']) }}"
                            @click="mobileNav = false"

                            class="admin-sidebar__link {{ $active ? 'is-active' : '' }}"
                            @if ($active) aria-current="page" @endif
                            :aria-label="collapsed ? @js($item['label']) : false"
                            :data-tooltip="collapsed ? @js($item['label']) : null"
                        >
                            <span class="inline-flex size-5 shrink-0 items-center justify-center text-white/50" aria-hidden="true">
                                @include('admin.partials.nav-icon', ['name' => $item['icon']])
                            </span>
                            <span class="admin-sidebar__label">{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endforeach
    </nav>

    <div class="admin-sidebar__foot">
        <button
            type="button"
            class="admin-sidebar__collapse"
            @click="toggleCollapsed()"
            :aria-expanded="(! collapsed).toString()"
            :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
            :data-tooltip="collapsed ? 'Expand menu' : 'Collapse menu'"
        >
            <span class="admin-sidebar__collapse-icon" aria-hidden="true">
                <svg class="size-4 transition-transform duration-200" :class="collapsed ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                    <rect x="3.75" y="4.75" width="16.5" height="14.5" rx="2" />
                    <path d="M9.25 5v14" stroke-linecap="round" />
                    <path d="M14.25 9.75 11.75 12l2.5 2.25" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
            <span class="admin-sidebar__label" x-text="collapsed ? 'Expand' : 'Collapse'"></span>
        </button>

        <a
            href="{{ route('home') }}"
            class="admin-sidebar__link"
            target="_blank"
            rel="noopener"

            :aria-label="collapsed ? 'View storefront' : false"
            :data-tooltip="collapsed ? 'View storefront' : null"
        >
            <span class="inline-flex size-5 shrink-0 items-center justify-center" aria-hidden="true">
                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M11 3a1 1 0 1 0 0 2h2.586l-6.293 6.293a1 1 0 1 0 1.414 1.414L15 6.414V9a1 1 0 1 0 2 0V4a1 1 0 0 0-1-1h-5Z"/>
                    <path d="M5 5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-3a1 1 0 1 0-2 0v3H5V7h3a1 1 0 0 0 0-2H5Z"/>
                </svg>
            </span>
            <span class="admin-sidebar__label">View storefront</span>
        </a>
    </div>
</aside>
