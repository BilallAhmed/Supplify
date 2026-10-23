<?php

namespace App\Http\Requests\Admin;

use App\Enums\AssistantSourceType;
use App\Models\AssistantFaq;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssistantSourceApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('approveSources', AssistantFaq::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'source_type' => ['required', 'string', Rule::enum(AssistantSourceType::class)],
            'source_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
