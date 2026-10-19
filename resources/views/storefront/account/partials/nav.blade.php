@php
    $links = [
        ['label' => 'Overview', 'route' => 'account.dashboard', 'match' => 'account.dashboard'],
        ['label' => 'Orders', 'route' => 'account.orders.index', 'match' => 'account.orders.*'],
        ['label' => 'Addresses', 'route' => 'account.addresses.index', 'match' => 'account.addresses.*'],
        ['label' => 'Profile', 'route' => 'account.profile.edit', 'match' => 'account.profile.*'],
    ];
@endphp

<nav class="account-nav mb-8" aria-label="Account">
    @foreach ($links as $link)
        @php $active = request()->routeIs($link['match']); @endphp
        <a
            href="{{ route($link['route']) }}"
            @class(['account-nav__link', 'is-active' => $active])
            @if ($active) aria-current="page" @endif
        >
            {{ $link['label'] }}
        </a>
    @endforeach
</nav>
