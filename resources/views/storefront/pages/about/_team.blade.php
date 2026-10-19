@php
    $enabled = (bool) ($section['enabled'] ?? false);
    $members = $section['members'] ?? [];
@endphp
@if ($enabled && count($members) > 0)
<section class="about-team" aria-labelledby="about-team-heading">
    <x-container class="about-team__inner">
        <header class="about-team__header">
            <h2 id="about-team-heading" class="about-team__title">{{ $section['heading'] ?? 'Our team' }}</h2>
            @if (! empty($section['intro']))
                <p class="about-team__intro">{{ $section['intro'] }}</p>
            @endif
        </header>
        <ul class="about-team__grid">
            @foreach ($members as $member)
                <li class="about-team__card">
                    @if (! empty($member['image']))
                        <img
                            src="{{ asset(ltrim($member['image'], '/')) }}"
                            alt="{{ $member['name'] ?? '' }}"
                            class="about-team__photo"
                            width="320"
                            height="400"
                            loading="lazy"
                            decoding="async"
                        >
                    @endif
                    <h3 class="about-team__name">{{ $member['name'] ?? '' }}</h3>
                    @if (! empty($member['role']))
                        <p class="about-team__role">{{ $member['role'] }}</p>
                    @endif
                    @if (! empty($member['bio']))
                        <p class="about-team__bio">{{ $member['bio'] }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
    </x-container>
</section>
@endif
