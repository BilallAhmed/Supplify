@props(['product'])
@php
    $variable = $product->isVariable();
    $temporary = $product->hasTemporaryPricing();
    $canAdd = ! $variable && ! $temporary && $product->isPurchasable();
@endphp
<div class="product-tile__actions home-formula-actions">
    <button type="button" class="product-tile__btn product-tile__btn--secondary" data-product-action="quick-view" data-product-slug="{{ $product->slug }}" aria-label="Quick view {{ $product->name }}">Quick View</button>
    @if ($canAdd)
        <button type="button" class="product-tile__btn product-tile__btn--primary" data-product-action="add-to-cart" data-product-id="{{ $product->id }}" aria-label="Add {{ $product->name }} to cart">Add to Cart</button>
    @elseif ($variable && ! $temporary)
        <button type="button" class="product-tile__btn product-tile__btn--primary" data-product-action="quick-view" data-product-slug="{{ $product->slug }}" aria-label="Select options for {{ $product->name }}">Select Options</button>
    @else
        <button type="button" class="product-tile__btn product-tile__btn--primary" disabled title="{{ $temporary ? 'Available to purchase once pricing is confirmed' : 'Currently unavailable for purchase' }}">Add to Cart</button>
    @endif
</div>
