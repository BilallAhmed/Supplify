@php $isEdit = $category->exists; @endphp

<form
    method="POST"
    action="{{ $isEdit ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
    class="mx-auto max-w-2xl space-y-4"
>
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <x-card class="space-y-4">
        <x-input label="Name" name="name" required :value="old('name', $category->name)" />
        <x-input label="Slug" name="slug" help="Leave blank to auto-generate from the name." :value="old('slug', $category->slug)" />
        <x-select label="Parent category" name="parent_id">
            <option value="">None</option>
            @foreach ($parents as $parent)
                <option value="{{ $parent->id }}" @selected((string) old('parent_id', $category->parent_id) === (string) $parent->id)>
                    {{ $parent->name }}
                </option>
            @endforeach
        </x-select>
        <x-textarea label="Description" name="description">{{ old('description', $category->description) }}</x-textarea>
        <x-input label="Sort order" name="sort_order" type="number" min="0" :value="old('sort_order', $category->sort_order ?? 0)" />
        <x-checkbox
            name="is_active"
            value="1"
            label="Active on storefront"
            :checked="(bool) old('is_active', $category->is_active)"
        />
        <x-input label="SEO title" name="seo_title" :value="old('seo_title', $category->seo_title)" />
        <x-textarea label="SEO description" name="seo_description" rows="2">{{ old('seo_description', $category->seo_description) }}</x-textarea>
    </x-card>

    <x-card class="space-y-4">
        <div>
            <h2 class="font-display text-lg font-bold text-ink">Shop by Goals icon</h2>
            <p class="mt-1 text-sm text-ink-muted">
                Choose a built-in library icon, or pick a custom image from the media library. SVG is not accepted.
            </p>
        </div>

        <x-select label="Built-in icon" name="icon_key" help="Used when no custom image is set.">
            <option value="">Auto from category slug</option>
            @foreach (\App\Models\Category::GOAL_ICON_KEYS as $key)
                <option value="{{ $key }}" @selected(old('icon_key', $category->icon_key) === $key)>
                    {{ str($key)->headline() }}
                </option>
            @endforeach
        </x-select>

        <x-media-picker
            name="icon_path"
            label="Custom icon image"
            :value="old('icon_path', $category->icon_path)"
            help="Optional. Prefer PNG or WebP. Removing clears the association only."
        />

        @unless ($category->customIconUrl() || old('icon_path', $category->icon_path))
            <div class="flex items-center gap-3 text-sm text-ink-muted">
                <span class="flex size-12 items-center justify-center rounded-[var(--radius-control)] border border-line bg-brand-mist/40 text-brand">
                    <x-goal-icon :name="$category->resolvedIconKey()" class="size-6" />
                </span>
                <span>Preview of built-in fallback</span>
            </div>
        @endunless
    </x-card>

    <div class="flex flex-wrap gap-3">
        <x-button type="submit" variant="primary">{{ $isEdit ? 'Save changes' : 'Create category' }}</x-button>
        <x-button href="{{ route('admin.categories.index') }}" variant="ghost">Cancel</x-button>
    </div>
</form>
