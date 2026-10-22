<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HomepageSectionRequest;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Post;
use App\Models\Product;
use App\Support\HomepageLayout;
use App\Support\MediaUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HomepageController extends Controller
{
    public function edit(): View
    {
        $this->authorize('viewAny', HomepageSection::class);
        $sections = HomepageSection::query()->whereIn('key', HomepageLayout::ORDER)->get()->sortBy(fn ($s) => array_search($s->key, HomepageLayout::ORDER))->values();

        return view('admin.homepage.edit', [
            'sections' => $sections,
            'products' => Product::query()->published()->orderBy('name')->get(['id', 'name', 'sku']),
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'posts' => Post::query()->published()->latest('published_at')->get(['id', 'title']),
        ]);
    }

    public function update(HomepageSectionRequest $request, HomepageSection $section): RedirectResponse
    {
        $this->authorize('update', $section);
        abort_unless(in_array($section->key, HomepageLayout::ORDER, true), 404);
        $data = $request->validated();
        $values = HomepageLayout::values($section);
        foreach (HomepageLayout::fields($section->key) as $field) {
            $value = data_get($data, $field['path']);
            if ($field['type'] === 'media') {
                $value = MediaUrl::normalize($value);
            }
            data_set($values, $field['path'], $value);
        }
        $values['is_enabled'] = $request->boolean('is_enabled');
        unset($values['key']);
        $section->fill($values)->save();

        return redirect()->route('admin.homepage.edit', ['section' => $section->key])->with('flash_success', HomepageLayout::labels()[$section->key].' saved.');
    }
}
