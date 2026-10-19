@php
    $items = $section->config['items'] ?? [];
    $icons = ['label', 'flask', 'package'];
@endphp

{{-- Floating assurance bridge: overlaps the hero edge, then eases into Shop by Goals --}}
<section class="home-assurance" aria-labelledby="assurance-heading">
    <x-container>
        <div class="home-assurance__inner">
            <h2 id="assurance-heading" class="sr-only">{{ $section->title ?: 'Shopping with confidence' }}</h2>

            @if (! empty($items))
                <ul class="home-assurance__rail">
                    @foreach ($items as $i => $item)
                        <li class="home-assurance__item">
                            <span class="home-assurance__icon" aria-hidden="true">
                                @php $icon = $item['icon'] ?? ($icons[$i] ?? 'leaf'); @endphp
                                @if ($icon === 'label')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M4 7.5h11.5L20 12l-4.5 4.5H4v-9Z" stroke-linejoin="round"/>
                                        <circle cx="8" cy="12" r="1.2" fill="currentColor" stroke="none"/>
                                    </svg>
                                @elseif ($icon === 'flask')
                                    <x-goal-icon name="flask" class="size-5" />
                                @elseif ($icon === 'package')
                                    <x-goal-icon name="package" class="size-5" />
                                @else
                                    <x-goal-icon :name="$icon" class="size-5" />
                                @endif
                            </span>
                            <span class="home-assurance__copy">
                                <span class="home-assurance__label">{{ $item['title'] ?? '' }}</span>
                                @if (! empty($item['body']))
                                    <span class="home-assurance__hint">{{ $item['body'] }}</span>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </x-container>
</section>
