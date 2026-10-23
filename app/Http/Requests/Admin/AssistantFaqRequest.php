<?php

namespace App\Http\Requests\Admin;

use App\Enums\AssistantIntent;
use App\Models\AssistantFaq;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssistantFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        $faq = $this->route('faq');

        if ($faq instanceof AssistantFaq) {
            return $this->user()?->can('update', $faq) ?? false;
        }

        return $this->user()?->can('create', AssistantFaq::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:5000'],
            'intent' => ['nullable', Rule::enum(AssistantIntent::class)],
            'aliases_text' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->filled('sort_order') ? (int) $this->input('sort_order') : 0,
            'intent' => $this->filled('intent') ? $this->input('intent') : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function faqAttributes(): array
    {
        $aliases = collect(preg_split('/[\r\n,]+/', (string) $this->input('aliases_text', '')))
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return [
            'question' => trim((string) $this->input('question')),
            'answer' => trim((string) $this->input('answer')),
            'intent' => $this->input('intent'),
            'aliases' => $aliases === [] ? null : $aliases,
            'sort_order' => (int) $this->input('sort_order', 0),
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
