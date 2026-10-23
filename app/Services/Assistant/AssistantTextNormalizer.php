<?php

namespace App\Services\Assistant;

use Illuminate\Support\Str;

class AssistantTextNormalizer
{
    public function normalize(?string $text): string
    {
        $text = html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = Str::of($text)
            ->lower()
            ->replaceMatches('/\s+/u', ' ')
            ->trim()
            ->toString();

        return $text;
    }

    /**
     * @return list<string>
     */
    public function tokens(?string $text): array
    {
        $normalized = $this->normalize($text);
        if ($normalized === '') {
            return [];
        }

        preg_match_all('/[\p{L}\p{N}]+/u', $normalized, $matches);

        return array_values(array_filter($matches[0] ?? [], fn (string $t) => mb_strlen($t) > 0));
    }

    /**
     * Whole-token phrase match — avoids "vitamin d3" silently matching "vitamin e".
     */
    public function containsPhrase(string $haystack, string $needle): bool
    {
        $haystack = $this->normalize($haystack);
        $needle = $this->normalize($needle);

        if ($haystack === '' || $needle === '') {
            return false;
        }

        if ($haystack === $needle || str_contains(' '.$haystack.' ', ' '.$needle.' ')) {
            return true;
        }

        $needleTokens = $this->tokens($needle);
        if ($needleTokens === []) {
            return false;
        }

        $hayTokens = $this->tokens($haystack);
        $hayCount = count($hayTokens);
        $needleCount = count($needleTokens);

        for ($i = 0; $i <= $hayCount - $needleCount; $i++) {
            $slice = array_slice($hayTokens, $i, $needleCount);
            if ($slice === $needleTokens) {
                return true;
            }
        }

        return false;
    }

    public function isExactNormalized(string $haystack, string $needle): bool
    {
        return $this->normalize($haystack) === $this->normalize($needle);
    }

    /**
     * @return list<array{field_key: string, content: string, normalized_content: string}>
     */
    public function chunkFields(array $fields, int $maxChars): array
    {
        $chunks = [];
        $index = 0;

        foreach ($fields as $fieldKey => $value) {
            $content = trim((string) $value);
            if ($content === '') {
                continue;
            }

            foreach ($this->splitBounded($content, $maxChars) as $piece) {
                $chunks[] = [
                    'field_key' => (string) $fieldKey,
                    'content' => $piece,
                    'normalized_content' => $this->normalize($piece),
                    'chunk_index' => $index,
                ];
                $index++;
            }
        }

        return $chunks;
    }

    /**
     * @return list<string>
     */
    public function splitBounded(string $content, int $maxChars): array
    {
        $maxChars = max(80, $maxChars);
        if (mb_strlen($content) <= $maxChars) {
            return [$content];
        }

        $parts = preg_split('/\n{2,}|(?<=[.!?])\s+/u', $content) ?: [$content];
        $out = [];
        $buffer = '';

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            if (mb_strlen($part) > $maxChars) {
                if ($buffer !== '') {
                    $out[] = $buffer;
                    $buffer = '';
                }
                foreach (str_split($part, $maxChars) as $slice) {
                    $out[] = $slice;
                }

                continue;
            }

            $candidate = $buffer === '' ? $part : $buffer.' '.$part;
            if (mb_strlen($candidate) <= $maxChars) {
                $buffer = $candidate;
            } else {
                $out[] = $buffer;
                $buffer = $part;
            }
        }

        if ($buffer !== '') {
            $out[] = $buffer;
        }

        return $out !== [] ? $out : [$content];
    }
}
