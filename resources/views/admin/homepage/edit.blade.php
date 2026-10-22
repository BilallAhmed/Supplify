@extends('layouts.admin')
@section('title', 'Homepage content')
@section('breadcrumb', 'Homepage')
@section('page_header')
    <div><h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Homepage content</h1><p class="mt-1 text-sm text-ink-muted">Edit the sections in the same order they appear on your storefront.</p></div>
    <x-button href="{{ route('home') }}" variant="outline" target="_blank">Preview homepage</x-button>
@endsection
@push('head')
<style>
.homepage-editor{display:grid;grid-template-columns:15rem minmax(0,1fr);gap:1.5rem;align-items:start}.homepage-editor__nav{position:sticky;top:6rem;padding:1rem;border:1px solid var(--color-line);border-radius:var(--radius-panel);background:var(--color-surface-elevated)}.homepage-editor__link{display:flex;gap:.6rem;padding:.7rem .6rem;border-radius:.6rem;font-size:.8125rem;color:var(--color-ink-muted)}.homepage-editor__link:hover,.homepage-editor__link.is-active{background:var(--color-surface);color:var(--color-ink)}.homepage-editor__panel{min-width:0;border:1px solid var(--color-line);border-radius:var(--radius-panel);background:var(--color-surface-elevated)}.homepage-editor__header{display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:1.25rem;border-bottom:1px solid var(--color-line)}.homepage-editor__group{padding:1.25rem;border-bottom:1px solid var(--color-line)}.homepage-editor__group h3{margin-bottom:1rem;font-size:.95rem;font-weight:700}.homepage-editor__fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.homepage-editor__field--wide{grid-column:1/-1}.homepage-editor__save button{white-space:nowrap;flex-shrink:0}.homepage-editor__save{position:sticky;bottom:1rem;display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:1rem 1.25rem;border-top:1px solid var(--color-line);background:var(--color-surface-elevated);border-radius:0 0 var(--radius-panel) var(--radius-panel);box-shadow:0 -6px 18px rgb(0 0 0/.04)}@media(max-width:1000px){.homepage-editor{grid-template-columns:1fr}.homepage-editor__nav{position:static}.homepage-editor__fields{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
@php
    $labels=\App\Support\HomepageLayout::labels();
    $active=old('_section', request('section', 'hero'));
    if(!$sections->contains('key',$active)) $active='hero';
@endphp
<div class="homepage-editor" x-data="{ active: @js($active) }">
    <nav class="homepage-editor__nav" aria-label="Homepage sections">
        <p class="px-2 pb-3 text-xs font-bold uppercase tracking-wider text-ink-subtle">Page sections</p>
        @foreach($sections as $section)
            <a class="homepage-editor__link" :class="{ 'is-active': active === @js($section->key) }" href="{{ route('admin.homepage.edit',['section'=>$section->key]) }}" @click.prevent="active = @js($section->key); history.replaceState(null, '', $el.href)" :aria-current="active === @js($section->key) ? 'page' : null"><span class="text-ink-subtle">{{ str_pad($loop->iteration,2,'0',STR_PAD_LEFT) }}</span><span>{{ $labels[$section->key] }}</span></a>
        @endforeach
        <p class="mt-4 border-t border-line px-2 pt-3 text-xs leading-relaxed text-ink-subtle">Images use your media library. Product names, prices and images come from the catalog. Edit articles under Blogs.</p>
    </nav>
    <div class="min-w-0">
    @foreach($sections as $section)
        @php $values=\App\Support\HomepageLayout::values($section); @endphp
        <form method="POST" action="{{ route('admin.homepage.update',$section) }}" class="homepage-editor__panel" x-show="active === @js($section->key)" @if($active!==$section->key) x-cloak @endif>
            @csrf @method('PUT')
            <input type="hidden" name="_section" value="{{ $section->key }}">
            <div class="homepage-editor__header"><div><h2 class="font-display text-xl font-bold text-ink">{{ $labels[$section->key] }}</h2><p class="mt-1 text-xs text-ink-subtle">Section {{ $loop->iteration }} of {{ $sections->count() }} · Changes apply after saving.</p></div><x-badge :variant="$section->is_enabled ? 'success' : 'neutral'">{{ $section->is_enabled ? 'Visible' : 'Hidden' }}</x-badge></div>
            @if(old('_section')===$section->key && $errors->any())<div class="m-5 rounded-lg border border-red-200 p-4 text-sm text-red-700" role="alert"><p class="font-bold">Please check these fields:</p><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @foreach(\App\Support\HomepageLayout::groups($section->key) as $group=>$fields)
                <fieldset class="homepage-editor__group"><legend class="sr-only">{{ $group }}</legend><h3>{{ $group }}</h3>
                    @if(in_array($group,['Products','Carousel products','Articles']))<p class="mb-4 text-xs text-ink-muted">Choose items in display order. Automatic slots use {{ $group==='Articles' ? 'the latest published articles' : 'published catalog products' }} and skip duplicates.</p>@endif
                    <div class="homepage-editor__fields">
                    @foreach($fields as $field)
                        @php
                            $path=$field['path']; $parts=explode('.',$path); $name=array_shift($parts); foreach($parts as $part) $name.='['.$part.']';
                            $value=old('_section')===$section->key ? old($path,data_get($values,$path)) : data_get($values,$path);
                            $id='home-'.$section->key.'-'.str_replace('.','-',$path);
                            $error=old('_section')===$section->key ? $errors->first($path) : null;
                        @endphp
                        <div class="{{ in_array($field['type'],['textarea','media']) ? 'homepage-editor__field--wide' : '' }}">
                        @if($field['type']==='media')
                            <x-media-picker :name="$name" :label="$field['label']" :value="$value" :field-id="$id" :use-old="false" help="Select an existing image or upload a new one. Keep the same composition for this layout." />
                        @elseif(in_array($field['type'],['product','banner_product','category','post','icon']))
                            @php $choices=match($field['type']) {'product','banner_product'=>$products,'category'=>$categories,'post'=>$posts,default=>collect(['energy','sleep','shield','gut','leaf','flask','heart','focus','package'])->map(fn($icon)=>(object)['id'=>$icon,'name'=>ucfirst($icon)])}; @endphp
                            <x-select :id="$id" :name="$name" :label="$field['label']" :error="$error">
                                @if(in_array($field['type'],['product','post']))<option value="">Automatic selection</option>@endif
                                @foreach($choices as $choice)<option value="{{ $choice->id }}" @selected((string)$value===(string)$choice->id)>{{ $choice->name ?? $choice->title }}{{ isset($choice->sku) ? ' · '.$choice->sku : '' }}</option>@endforeach
                            </x-select>
                        @elseif($field['type']==='textarea')
                            <x-textarea :id="$id" :name="$name" :label="$field['label']" :error="$error" rows="3">{{ $value }}</x-textarea>
                        @else
                            <x-input :id="$id" :name="$name" :label="$field['label']" :error="$error" :type="$field['type']==='number' ? 'number' : 'text'" :value="$value" />
                        @endif
                        </div>
                    @endforeach
                    </div>
                </fieldset>
            @endforeach
            <div class="homepage-editor__save"><x-checkbox id="enabled-{{ $section->key }}" name="is_enabled" value="1" label="Visible on homepage" :checked="old('_section')===$section->key ? (bool)old('is_enabled',false) : $section->is_enabled" /><x-button type="submit" variant="primary">Save {{ $labels[$section->key] }}</x-button></div>
        </form>
    @endforeach
    </div>
</div>
@endsection
