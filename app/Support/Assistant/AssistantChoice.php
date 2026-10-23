<?php

namespace App\Support\Assistant;

use App\Enums\AssistantIntent;

readonly class AssistantChoice
{
    public function __construct(
        public string $id,
        public string $label,
        public ?AssistantIntent $intent = null,
        public ?string $payload = null,
    ) {}

    /**
     * @return array{id: string, label: string, intent: ?string, payload: ?string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'intent' => $this->intent?->value,
            'payload' => $this->payload,
        ];
    }
}
