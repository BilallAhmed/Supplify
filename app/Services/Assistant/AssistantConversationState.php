<?php

namespace App\Services\Assistant;

use App\Enums\AssistantIntent;
use Carbon\Carbon;
use Illuminate\Contracts\Session\Session;

class AssistantConversationState
{
    public const SESSION_KEY = 'assistant.conversation';

    public function __construct(
        private readonly Session $session,
        private readonly AssistantSettings $settings,
    ) {}

    /**
     * @return array{
     *     updated_at: ?string,
     *     turn_count: int,
     *     intent: ?string,
     *     selected_product_id: ?int,
     *     offered_product_ids: list<int>,
     *     offered_source_ids: list<string>,
     *     discovery_topic: ?string,
     *     last_query: ?string
     * }
     */
    public function get(): array
    {
        $this->expireIfStale();

        /** @var array<string, mixed> $state */
        $state = $this->session->get(self::SESSION_KEY, $this->empty());

        return $this->normalize($state);
    }

    public function reset(): void
    {
        $this->session->forget([self::SESSION_KEY, 'assistant.transcript']);
    }

    public function touch(): void
    {
        $state = $this->get();
        $state['updated_at'] = now()->toIso8601String();
        $this->session->put(self::SESSION_KEY, $state);
    }

    public function incrementTurn(): int
    {
        $state = $this->get();
        $state['turn_count'] = (int) $state['turn_count'] + 1;
        $state['updated_at'] = now()->toIso8601String();
        $this->session->put(self::SESSION_KEY, $state);

        return $state['turn_count'];
    }

    public function setIntent(?AssistantIntent $intent): void
    {
        $state = $this->get();
        $state['intent'] = $intent?->value;
        $state['updated_at'] = now()->toIso8601String();
        $this->session->put(self::SESSION_KEY, $state);
    }

    public function setSelectedProductId(?int $productId): void
    {
        $state = $this->get();
        $state['selected_product_id'] = $productId;
        $state['updated_at'] = now()->toIso8601String();
        $this->session->put(self::SESSION_KEY, $state);
    }

    /**
     * @param  list<int>  $productIds
     * @param  list<string>  $sourceIds
     */
    public function setOffered(array $productIds, array $sourceIds, ?string $lastQuery = null): void
    {
        $state = $this->get();
        $state['offered_product_ids'] = array_values(array_unique(array_map('intval', $productIds)));
        $state['offered_source_ids'] = array_values(array_unique(array_map('strval', $sourceIds)));
        if ($lastQuery !== null) {
            $state['last_query'] = $lastQuery;
        }
        $state['updated_at'] = now()->toIso8601String();
        $this->session->put(self::SESSION_KEY, $state);
    }

    public function setTopic(?string $topic): void
    {
        $state = $this->get();
        $state['discovery_topic'] = $topic === null ? null : mb_substr($topic, 0, 120);
        $state['updated_at'] = now()->toIso8601String();
        $this->session->put(self::SESSION_KEY, $state);
    }

    public function selectedProductId(): ?int
    {
        $id = $this->get()['selected_product_id'];

        return $id ? (int) $id : null;
    }

    /**
     * @return list<int>
     */
    public function offeredProductIds(): array
    {
        return $this->get()['offered_product_ids'];
    }

    public function turnCount(): int
    {
        return $this->get()['turn_count'];
    }

    public function isExpired(): bool
    {
        $updatedAt = $this->session->get(self::SESSION_KEY.'.updated_at')
            ?? data_get($this->session->get(self::SESSION_KEY), 'updated_at');

        if (! $updatedAt) {
            return false;
        }

        $idle = $this->settings->limit('session_idle_minutes', 60);

        try {
            return now()->subMinutes($idle)->greaterThan(Carbon::parse((string) $updatedAt));
        } catch (\Throwable) {
            return false;
        }
    }

    private function expireIfStale(): void
    {
        $raw = $this->session->get(self::SESSION_KEY);
        if (! is_array($raw) || empty($raw['updated_at'])) {
            return;
        }

        $idle = $this->settings->limit('session_idle_minutes', 60);

        try {
            if (now()->subMinutes($idle)->greaterThan(Carbon::parse((string) $raw['updated_at']))) {
                $this->reset();
            }
        } catch (\Throwable) {
            // Keep state if timestamp is unreadable.
        }
    }

    /**
     * @return array{
     *     updated_at: null,
     *     turn_count: int,
     *     intent: null,
     *     selected_product_id: null,
     *     offered_product_ids: list<int>,
     *     offered_source_ids: list<string>,
     *     last_query: null
     * }
     */
    private function empty(): array
    {
        return [
            'updated_at' => null,
            'turn_count' => 0,
            'intent' => null,
            'selected_product_id' => null,
            'offered_product_ids' => [],
            'offered_source_ids' => [],
            'last_query' => null,
            'discovery_topic' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array{
     *     updated_at: ?string,
     *     turn_count: int,
     *     intent: ?string,
     *     selected_product_id: ?int,
     *     offered_product_ids: list<int>,
     *     offered_source_ids: list<string>,
     *     discovery_topic: ?string,
     *     last_query: ?string
     * }
     */
    private function normalize(array $state): array
    {
        $empty = $this->empty();

        return [
            'updated_at' => isset($state['updated_at']) ? (string) $state['updated_at'] : null,
            'turn_count' => max(0, (int) ($state['turn_count'] ?? 0)),
            'intent' => isset($state['intent']) ? (string) $state['intent'] : null,
            'selected_product_id' => isset($state['selected_product_id']) ? (int) $state['selected_product_id'] : null,
            'offered_product_ids' => array_values(array_map('intval', $state['offered_product_ids'] ?? [])),
            'offered_source_ids' => array_values(array_map('strval', $state['offered_source_ids'] ?? [])),
            'last_query' => isset($state['last_query']) ? (string) $state['last_query'] : null,
            'discovery_topic' => isset($state['discovery_topic']) ? mb_substr((string) $state['discovery_topic'], 0, 120) : null,
        ] + $empty;
    }
}
