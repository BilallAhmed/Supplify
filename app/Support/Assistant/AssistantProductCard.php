<?php

namespace App\Support\Assistant;

readonly class AssistantProductCard
{
    /**
     * @param  list<string>  $highlights
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public string $url,
        public string $price_formatted,
        public string $currency,
        public string $stock_label,
        public bool $purchasable,
        public ?string $image_url = null,
        public array $highlights = [],
    ) {}

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     url: string,
     *     price_formatted: string,
     *     currency: string,
     *     stock_label: string,
     *     purchasable: bool,
     *     image_url: ?string,
     *     highlights: list<string>
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'url' => $this->url,
            'price_formatted' => $this->price_formatted,
            'currency' => $this->currency,
            'stock_label' => $this->stock_label,
            'purchasable' => $this->purchasable,
            'image_url' => $this->image_url,
            'highlights' => array_values($this->highlights),
        ];
    }
}
