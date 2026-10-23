@php
    $isEdit = $alias->exists;
@endphp

<form
    method="POST"
    action="{{ $isEdit ? route('admin.assistant.aliases.update', $alias) : route('admin.assistant.aliases.store') }}"
    class="admin-form"
>
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <x-card class="admin-form__section">
        <div class="admin-form__section-head">
            <h2 class="admin-form__section-title">Alias details</h2>
            <p class="admin-form__section-desc">
                Reviewed phrases (English / Roman Urdu / Urdu) that map to an intent or source. Not full multilingual conversation.
            </p>
        </div>
        <div class="admin-form-grid">
            <div class="sm:col-span-2">
                <x-input
                    label="Phrase"
                    name="phrase"
                    required
                    :value="old('phrase', $alias->phrase)"
                    help="Normalized automatically for matching."
                />
            </div>
            <x-select label="Locale" name="locale" required>
                @foreach (['en' => 'English', 'roman_ur' => 'Roman Urdu', 'ur' => 'Urdu'] as $value => $label)
                    <option value="{{ $value }}" @selected((string) old('locale', $alias->locale ?? 'en') === $value)>{{ $label }}</option>
                @endforeach
            </x-select>
            <x-select label="Maps to intent" name="maps_to_intent">
                <option value="">None</option>
                @foreach ($intents as $intent)
                    <option value="{{ $intent->value }}" @selected((string) old('maps_to_intent', $alias->maps_to_intent?->value) === $intent->value)>
                        {{ $intent->label() }}
                    </option>
                @endforeach
            </x-select>
            <x-select label="Target type" name="target_type">
                <option value="">None</option>
                @foreach ($sourceTypes as $type)
                    <option value="{{ $type->value }}" @selected((string) old('target_type', $alias->target_type?->value) === $type->value)>
                        {{ ucfirst($type->value) }}
                    </option>
                @endforeach
            </x-select>
            <x-input
                label="Target ID"
                name="target_id"
                type="number"
                min="1"
                help="Optional. Pair with target type to point at a product, post, page, category, or FAQ."
                :value="old('target_id', $alias->target_id)"
            />
            <label class="flex items-start gap-3 text-sm text-ink sm:col-span-2">
                <input type="hidden" name="is_active" value="0">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    class="mt-1"
                    @checked(old('is_active', $alias->is_active ?? true))
                >
                <span>
                    <span class="font-medium">Active</span>
                    <span class="mt-1 block text-ink-muted">Inactive aliases are ignored by search.</span>
                </span>
            </label>
        </div>
        <div class="mt-6 flex flex-wrap gap-3">
            <x-button type="submit" variant="primary">{{ $isEdit ? 'Save alias' : 'Create alias' }}</x-button>
            <x-button href="{{ route('admin.assistant.aliases.index') }}" variant="ghost">Cancel</x-button>
        </div>
    </x-card>
</form>
