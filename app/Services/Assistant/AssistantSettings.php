<?php

namespace App\Services\Assistant;

use App\Services\StoreSettings;

class AssistantSettings
{
    public function __construct(
        private readonly StoreSettings $store,
    ) {}

    /**
     * Widget and public chat remain off until config and runtime both allow it.
     */
    public function isEnabled(): bool
    {
        if (! (bool) config('assistant.enabled', false)) {
            return false;
        }

        return (bool) $this->store->get('assistant.enabled', false);
    }

    public function setEnabled(bool $enabled): void
    {
        $this->store->putMany([
            'assistant.enabled' => $enabled,
        ]);
    }

    public function publicName(): string
    {
        return (string) config('assistant.public_name', 'Supplify Assistant');
    }

    public function tagline(): string
    {
        return (string) config('assistant.tagline', '');
    }

    /**
     * @return array<string, int>
     */
    public function limits(): array
    {
        /** @var array<string, int> $limits */
        $limits = config('assistant.limits', []);

        return $limits;
    }

    public function limit(string $key, int $default = 0): int
    {
        return (int) ($this->limits()[$key] ?? $default);
    }
}
