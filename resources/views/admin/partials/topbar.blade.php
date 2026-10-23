<header class="sticky top-0 z-30 flex h-16 items-center justify-between gap-3 border-b border-line bg-surface-elevated/95 px-4 backdrop-blur lg:px-6">
    <div class="flex items-center gap-3">
        <button
            type="button"
            class="inline-flex items-center justify-center rounded-[var(--radius-control)] border border-line p-2 text-ink lg:hidden"
            @click="mobileNav = !mobileNav"
            :aria-expanded="mobileNav.toString()"
            aria-controls="admin-sidebar"
            aria-label="Open navigation"
        >
            <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M2 5.75A.75.75 0 0 1 2.75 5h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 5.75Zm0 4.25a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 10Zm0 4.25a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H2.75a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd"/>
            </svg>
        </button>

        <div class="min-w-0">
            <p class="truncate text-sm font-medium text-ink">@yield('breadcrumb', 'Admin')</p>
        </div>
    </div>

    <div class="flex items-center gap-2 sm:gap-3">
        <button
            type="button"
            class="admin-theme-toggle"
            @click="toggleTheme()"
            :aria-label="darkMode ? 'Switch to light mode' : 'Switch to dark mode'"
            :data-tooltip="darkMode ? 'Light mode' : 'Dark mode'"
        >
            <svg x-show="!darkMode" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path d="M21 14.3A8.4 8.4 0 0 1 9.7 3 7 7 0 1 0 21 14.3Z" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <svg x-show="darkMode" x-cloak class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <circle cx="12" cy="12" r="4"/>
                <path d="M12 2.5v2.2M12 19.3v2.2M4.7 4.7l1.6 1.6M17.7 17.7l1.6 1.6M2.5 12h2.2M19.3 12h2.2M4.7 19.3l1.6-1.6M17.7 6.3l1.6-1.6" stroke-linecap="round"/>
            </svg>
        </button>

        <span class="hidden text-sm text-ink-muted sm:inline">{{ auth()->user()->name }}</span>
        <x-badge variant="brand">Admin</x-badge>
        <x-button href="{{ route('account.profile.edit') }}" variant="ghost" size="sm" class="hidden sm:inline-flex">Profile</x-button>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <x-button type="submit" variant="outline" size="sm">Sign out</x-button>
        </form>
    </div>
</header>
