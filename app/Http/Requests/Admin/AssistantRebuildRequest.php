<?php

namespace App\Http\Requests\Admin;

use App\Models\AssistantFaq;
use Illuminate\Foundation\Http\FormRequest;

class AssistantRebuildRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageSettings', AssistantFaq::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sync' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sync' => $this->boolean('sync'),
        ]);
    }
}
