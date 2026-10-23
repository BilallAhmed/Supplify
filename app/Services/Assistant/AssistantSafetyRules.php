<?php

namespace App\Services\Assistant;

use App\Enums\AssistantIntent;
use App\Support\Assistant\AssistantResponse;
use Illuminate\Support\Str;

class AssistantSafetyRules
{
    public function __construct(
        private readonly AssistantTextNormalizer $normalizer,
    ) {}

    /**
     * Strip tags/scripts; treat user text as data only.
     */
    public function sanitizeUserMessage(string $message): string
    {
        $message = html_entity_decode($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $message = strip_tags($message);
        $message = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $message) ?? '';
        $message = trim(Str::limit($message, (int) config('assistant.limits.message_max_length', 500), ''));

        return $message;
    }

    public function sanitizeExcerpt(?string $text, int $max = 400): string
    {
        $clean = trim(strip_tags(html_entity_decode((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return Str::limit($clean, $max, '…');
    }

    public function looksLikeInstructionInjection(string $message): bool
    {
        $normalized = $this->normalizer->normalize($message);
        $needles = [
            'ignore previous',
            'ignore all instructions',
            'system prompt',
            'you are now',
            'act as',
            'jailbreak',
            'reveal your prompt',
            'execute sql',
            '<script',
            'javascript:',
        ];

        foreach ($needles as $needle) {
            if (str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }

    public function boundaryResponse(): AssistantResponse
    {
        return AssistantResponse::boundary(
            'Supplify Assistant can share published product and policy information from this website, '
            .'but cannot diagnose conditions, prescribe supplements, advise on medication interactions, '
            .'pregnancy, children, or decide whether a product is suitable for you. '
            .'Please consult a qualified healthcare professional. For store help, use Contact Us.'
        );
    }

    public function injectionResponse(): AssistantResponse
    {
        return AssistantResponse::unknown(
            'I can only help with Supplify’s published website information—products, articles, and policies. '
            .'Try asking about a product name, ingredient on a label, shipping, or returns.'
        );
    }

    public function isBoundaryIntent(AssistantIntent $intent): bool
    {
        return $intent === AssistantIntent::Boundary;
    }
}
