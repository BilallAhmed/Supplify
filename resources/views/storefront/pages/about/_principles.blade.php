@if (($section['enabled'] ?? true) !== false)
@php $items = $section['items'] ?? []; @endphp
<section class="about-principles" aria-labelledby="about-principles-heading">
    <x-container class="about-principles__inner">
        <header class="about-principles__header">
            <h2 id="about-principles-heading" class="about-principles__title">
                {{ $section['heading'] ?? 'What guides our work' }}
            </h2>
            @if (! empty($section['intro']))
                <p class="about-principles__intro">{{ $section['intro'] }}</p>
            @endif
        </header>

        @if (count($items) > 0)
            <ol class="about-principles__grid">
                @foreach ($items as $i => $item)
                    <li class="about-principles__item">
                        <span class="about-principles__num" aria-hidden="true">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="about-principles__icon" aria-hidden="true">
                            <x-goal-icon :name="$item['icon'] ?? 'leaf'" class="size-6" />
                        </span>
                        <h3 class="about-principles__item-title">{{ $item['title'] ?? '' }}</h3>
                        @if (! empty($item['body']))
                            <p class="about-principles__item-body">{{ $item['body'] }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif
    </x-container>
</section>
@endif
