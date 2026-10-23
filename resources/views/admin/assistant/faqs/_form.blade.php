@php
    $isEdit = $faq->exists;
    $aliasesText = old('aliases_text');
    if ($aliasesText === null) {
        $aliasesText = is_array($faq->aliases) ? implode("\n", $faq->aliases) : '';
    }
@endphp

<form
    method="POST"
    action="{{ $isEdit ? route('admin.assistant.faqs.update', $faq) : route('admin.assistant.faqs.store') }}"
    class="admin-form"
>
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <x-card class="admin-form__section">
        <div class="admin-form__section-head">
            <h2 class="admin-form__section-title">FAQ details</h2>
            <p class="admin-form__section-desc">Approved website answers only. No medical diagnosis or dosing advice.</p>
        </div>
        <div class="admin-form-grid">
            <div class="sm:col-span-2">
                <x-input
                    label="Question"
                    name="question"
                    required
                    :value="old('question', $faq->question)"
                />
            </div>
            <div class="sm:col-span-2">
                <x-textarea
                    label="Answer"
                    name="answer"
                    required
                    rows="6"
                    help="Quoted or paraphrased website policy/product help only."
                >{{ old('answer', $faq->answer) }}</x-textarea>
            </div>
            <x-select label="Intent" name="intent">
                <option value="">No specific intent</option>
                @foreach ($intents as $intent)
                    <option value="{{ $intent->value }}" @selected((string) old('intent', $faq->intent?->value) === $intent->value)>
                        {{ $intent->label() }}
                    </option>
                @endforeach
            </x-select>
            <x-input
                label="Sort order"
                name="sort_order"
                type="number"
                min="0"
                :value="old('sort_order', $faq->sort_order ?? 0)"
            />
            <div class="sm:col-span-2">
                <x-textarea
                    label="Aliases"
                    name="aliases_text"
                    rows="3"
                    help="Optional alternate phrasings. One per line or comma-separated."
                >{{ $aliasesText }}</x-textarea>
            </div>
            <label class="flex items-start gap-3 text-sm text-ink sm:col-span-2">
                <input type="hidden" name="is_active" value="0">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    class="mt-1"
                    @checked(old('is_active', $faq->is_active ?? true))
                >
                <span>
                    <span class="font-medium">Active</span>
                    <span class="mt-1 block text-ink-muted">Inactive FAQs are not searchable.</span>
                </span>
            </label>
        </div>
        <div class="mt-6 flex flex-wrap gap-3">
            <x-button type="submit" variant="primary">{{ $isEdit ? 'Save FAQ' : 'Create FAQ' }}</x-button>
            <x-button href="{{ route('admin.assistant.faqs.index') }}" variant="ghost">Cancel</x-button>
        </div>
    </x-card>
</form>
