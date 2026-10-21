@php
    $tabs = [
        [
            'label' => 'Store',
            'route' => 'admin.settings.edit',
            'active' => request()->routeIs('admin.settings.edit'),
        ],
        [
            'label' => 'Tracking & Scripts',
            'route' => 'admin.settings.tracking.edit',
            'active' => request()->routeIs('admin.settings.tracking.*'),
        ],
    ];
@endphp

<nav class="admin-settings-nav" aria-label="Settings sections">
    @foreach ($tabs as $tab)
        <a
            href="{{ route($tab['route']) }}"
            class="admin-settings-nav__link {{ $tab['active'] ? 'is-active' : '' }}"
            @if ($tab['active']) aria-current="page" @endif
        >
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
