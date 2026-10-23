<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class AssistantMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $max = (int) config('assistant.limits.message_max_length', 500);

        return [
            'message' => ['nullable', 'string', 'max:'.$max],
            'choice_id' => ['nullable', 'string', 'max:120'],
            'choice_payload' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $message = trim((string) $this->input('message', ''));
            $choice = trim((string) $this->input('choice_id', ''));
            $payload = trim((string) $this->input('choice_payload', ''));

            if ($message === '' && $choice === '' && $payload === '') {
                $validator->errors()->add('message', 'Enter a message or choose a follow-up option.');
            }
        });
    }
}
