<?php

namespace App\Support\Assistant;

use App\Enums\AssistantSourceType;

readonly class AssistantSourceReference
{
    public function __construct(
        public AssistantSourceType $type,
        public int $id,
        public string $title,
        public string $url,
        public ?string $revision = null,
    ) {}

    /**
     * @return array{type: string, id: int, title: string, url: string, revision: ?string}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'revision' => $this->revision,
        ];
    }
}
