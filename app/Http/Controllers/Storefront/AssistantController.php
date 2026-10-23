<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\AssistantMessageRequest;
use App\Services\Assistant\AssistantConversationService;
use App\Services\Assistant\AssistantSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class AssistantController extends Controller
{
    public function __construct(
        private readonly AssistantConversationService $conversation,
        private readonly AssistantSettings $settings,
    ) {}

    public function message(AssistantMessageRequest $request): JsonResponse
    {
        if (! $this->settings->isEnabled()) {
            return response()->json([
                'ok' => false,
                'error' => 'Supplify Assistant is not enabled.',
            ], 503);
        }

        // State expiry clears the transcript before appending the next turn.
        $this->conversation->snapshot();
        $history = session('assistant.transcript', []);
        $history[] = ['id' => 'user-'.Str::uuid(), 'role' => 'user', 'text' => (string) $request->input('message', ''), 'isError' => false, 'response' => null];
        session()->put('assistant.transcript', array_slice($history, -40));
        $result = $this->conversation->handle(
            message: (string) $request->input('message', ''),
            choiceId: $request->input('choice_id'),
            choicePayload: $request->input('choice_payload'),
        );

        return response()->json([
            'ok' => true,
            'assistant' => $this->settings->publicName(),
            'response' => $result['response']->toArray(),
            'state' => [
                'turn_count' => $result['state']['turn_count'],
                'selected_product_id' => $result['state']['selected_product_id'],
            ],
        ]);
    }

    public function history(): JsonResponse
    {
        if (! $this->settings->isEnabled()) {
            return response()->json(['ok' => false], 503);
        }
        $this->conversation->snapshot();

        return response()->json(['ok' => true, 'messages' => session('assistant.transcript', [])])->header('Cache-Control', 'no-store, private');
    }

    public function reset(): JsonResponse
    {
        if (! $this->settings->isEnabled()) {
            return response()->json([
                'ok' => false,
                'error' => 'Supplify Assistant is not enabled.',
            ], 503);
        }

        $this->conversation->reset();

        return response()->json([
            'ok' => true,
            'message' => 'Assistant chat reset.',
            'state' => $this->conversation->snapshot(),
        ]);
    }
}
