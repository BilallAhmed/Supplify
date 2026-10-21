@php
    /** @var \App\Models\Post $post */
@endphp

<div class="admin-form__section">
    <div class="admin-form-grid">
        <x-input id="title" name="title" label="Title" :value="old('title', $post->title)" required />
        <x-input id="slug" name="slug" label="Slug" :value="old('slug', $post->slug)" placeholder="auto-from-title" help="Leave blank to generate from the title." />

        <x-textarea id="excerpt" name="excerpt" label="Excerpt" rows="3" help="Short summary shown on the blog listing and search results.">{{ old('excerpt', $post->excerpt) }}</x-textarea>

        <x-textarea id="body" name="body" label="Body" rows="14" help="Plain text is fine. Line breaks are preserved on the storefront.">{{ old('body', $post->body) }}</x-textarea>

        <x-select id="post_category_id" name="post_category_id" label="Category">
            <option value="">No category</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('post_category_id', $post->post_category_id) === (string) $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </x-select>

        <x-input id="tags" name="tags" label="Tags" :value="old('tags', $tagsValue)" help="Comma-separated (e.g. sleep, magnesium)." />

        <x-media-picker
            name="featured_image"
            label="Featured image"
            :value="old('featured_image', $post->featured_image)"
            help="Select from the media library or upload a new image."
        />

        <x-input id="seo_title" name="seo_title" label="SEO title" :value="old('seo_title', $post->seo_title)" />
        <x-input id="seo_description" name="seo_description" label="SEO description" :value="old('seo_description', $post->seo_description)" />

        <x-checkbox
            name="is_published"
            value="1"
            label="Published"
            help="Visible on the storefront when enabled."
            :checked="(bool) old('is_published', $post->is_published)"
        />

        <x-checkbox
            name="is_featured"
            value="1"
            label="Featured on journal listing"
            help="Shows as the lead story on the unfiltered Blogs page. Only one post can be featured at a time."
            :checked="(bool) old('is_featured', $post->is_featured)"
        />
    </div>
</div>
