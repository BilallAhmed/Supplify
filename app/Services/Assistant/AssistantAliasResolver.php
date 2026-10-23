<?php

namespace App\Services\Assistant;

use App\Enums\AssistantIntent;
use App\Enums\AssistantSourceType;
use App\Models\AssistantAlias;
use Illuminate\Support\Collection;

class AssistantAliasResolver
{
    public function __construct(
        private readonly AssistantTextNormalizer $normalizer,
    ) {}

    /**
     * @return Collection<int, AssistantAlias>
     */
    public function resolve(string $query): Collection
    {
        $normalized = $this->normalizer->normalize($query);
        if ($normalized === '') {
            return collect();
        }

        $aliases = AssistantAlias::query()
            ->active()
            ->get();

        return $aliases
            ->filter(function (AssistantAlias $alias) use ($normalized) {
                $phrase = $this->normalizer->normalize($alias->normalized_phrase ?: $alias->phrase);

                return $phrase !== '' && (
                    $phrase === $normalized
                    || $this->normalizer->containsPhrase($normalized, $phrase)
                    || $this->normalizer->containsPhrase($phrase, $normalized)
                );
            })
            ->sortByDesc(fn (AssistantAlias $alias) => mb_strlen($alias->normalized_phrase ?: $alias->phrase))
            ->values();
    }

    public function primaryIntent(string $query): ?AssistantIntent
    {
        $hit = $this->resolve($query)->first(fn (AssistantAlias $alias) => $alias->maps_to_intent !== null);

        return $hit?->maps_to_intent;
    }

    /**
     * @return list<array{type: AssistantSourceType, id: int, alias: string}>
     */
    public function targetHits(string $query): array
    {
        $out = [];

        foreach ($this->resolve($query) as $alias) {
            if ($alias->target_type && $alias->target_id) {
                $out[] = [
                    'type' => $alias->target_type,
                    'id' => (int) $alias->target_id,
                    'alias' => $alias->phrase,
                ];
            }
        }

        return $out;
    }
}
