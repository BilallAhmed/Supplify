@php
    /** @var \App\Models\Post $post */
@endphp

<div class="space-y-6">
    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <x-label for="title">Title</x-label>
            <x-input id="title" name="title" :value="old('title', $post->title)" required />
        </div>
        <div>
            <x-label for="slug">Slug</x-label>
            <x-input id="slug" name="slug" :value="old('slug', $post->slug)" placeholder="auto-from-title" />
        </div>
    </div>

    <div>
        <x-label for="excerpt">Excerpt</x-label>
        <x-textarea id="excerpt" name="excerpt" rows="3">{{ old('excerpt', $post->excerpt) }}</x-textarea>
        <p class="ui-help">Short summary shown on the blog listing and search results.</p>
    </div>

    <div>
        <x-label for="body">Body</x-label>
        <x-textarea id="body" name="body" rows="14">{{ old('body', $post->body) }}</x-textarea>
        <p class="ui-help">Plain text is fine. Line breaks are preserved on the storefront.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <x-label for="post_category_id">Category</x-label>
            <x-select id="post_category_id" name="post_category_id">
                <option value="">No category</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('post_category_id', $post->post_category_id) === (string) $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </x-select>
        </div>
        <div>
            <x-label for="tags">Tags</x-label>
            <x-input id="tags" name="tags" :value="old('tags', $tagsValue)" help="Comma-separated (e.g. sleep, magnesium)." />
        </div>
    </div>

    <x-media-picker
        name="featured_image"
        label="Featured image"
        :value="old('featured_image', $post->featured_image)"
        help="Select from the media library or upload a new image."
    />

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <x-label for="seo_title">SEO title</x-label>
            <x-input id="seo_title" name="seo_title" :value="old('seo_title', $post->seo_title)" />
        </div>
        <div>
            <x-label for="seo_description">SEO description</x-label>
            <x-input id="seo_description" name="seo_description" :value="old('seo_description', $post->seo_description)" />
        </div>
    </div>

    <label class="flex cursor-pointer items-start gap-3">
        <input
            type="checkbox"
            name="is_published"
            value="1"
            class="mt-1 size-4 rounded border-line text-brand focus:ring-brand/30"
            @checked((bool) old('is_published', $post->is_published))
        >
        <span class="text-sm text-ink">Published</span>
    </label>
</div>
