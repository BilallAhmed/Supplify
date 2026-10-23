<?php

namespace App\Support\Assistant;

use App\Enums\AssistantSourceType;

readonly class AssistantContentCard
{
    public function __construct(
        public AssistantSourceType $type,
        public int $id,
        public string $title,
        public string $url,
        public ?string $excerpt = null,
        public ?string $meta = null,
        public ?string $image_url = null,
    ) {}

    /**
     * @return array{
     *     type: string,
     *     id: int,
     *     title: string,
     *     url: string,
     *     excerpt: ?string,
     *     meta: ?string,
     *     image_url: ?string
     * }
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'excerpt' => $this->excerpt,
            'meta' => $this->meta,
            'image_url' => $this->image_url,
        ];
    }
}
