@props([
    'product',
])

@php
    $image = $product->primaryImage();
    $href = route('products.show', $product->slug);
    $isVariable = $product->isVariable();
@endphp

<article {{ $attributes->merge(['class' => 'group flex h-full flex-col overflow-hidden rounded-[var(--radius-panel)] border border-line/80 bg-surface-elevated shadow-soft transition duration-200 hover:-translate-y-0.5 hover:shadow-lift']) }}>
    <a
        href="{{ $href }}"
        class="product-card__media relative block aspect-square overflow-hidden bg-brand-mist"
        data-product-media
        aria-label="{{ $product->name }}"
    >
        @if ($image)
            <img
                src="{{ $image->url() }}"
                alt="{{ $image->alt ?: $product->name }}"
                class="product-card__img size-full object-contain p-4 transition duration-300 group-hover:scale-[1.02]"
                data-product-cutout
                loading="lazy"
                width="480"
                height="480"
            >
        @else
            <img
                src="{{ $product->displayImageUrl() }}"
                alt="{{ $product->name }}"
                class="product-card__img size-full object-contain p-4 transition duration-300 group-hover:scale-[1.02]"
                data-product-cutout
                loading="lazy"
                width="480"
                height="480"
            >
        @endif

        @if ($product->onSale())
            <span class="absolute top-3 left-3">
                <x-badge variant="accent">Sale</x-badge>
            </span>
        @endif
    </a>

    <div class="flex flex-1 flex-col p-4 sm:p-5">
        @if ($product->categories->isNotEmpty())
            <p class="text-xs font-medium tracking-wide text-ink-subtle uppercase">
                {{ $product->categories->first()->name }}
            </p>
        @endif

        <h3 class="font-display mt-1 text-lg font-bold tracking-tight text-ink">
            <a href="{{ $href }}" class="hover:text-brand">{{ $product->name }}</a>
        </h3>

        @if ($product->short_description)
            <p class="mt-2 line-clamp-2 text-sm text-ink-muted">{{ $product->short_description }}</p>
        @endif

        <div class="mt-auto flex items-end justify-between gap-3 pt-4">
            <div>
                <p class="font-display text-lg font-bold text-ink">{{ $product->effectivePriceFormatted() }}</p>
                @if (! $isVariable && $product->onSale())
                    <p class="text-xs text-ink-subtle line-through">{{ $product->priceFormatted() }}</p>
                @endif
            </div>
            @if ($isVariable)
                <a href="{{ $href }}" class="text-xs font-semibold text-brand hover:underline">Select options</a>
            @else
                <x-badge :variant="$product->stock_status->badgeVariant()">
                    {{ $product->stock_status->label() }}
                </x-badge>
            @endif
        </div>
    </div>
</article>
