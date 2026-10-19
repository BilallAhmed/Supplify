<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HomepageSectionRequest;
use App\Models\HomepageSection;
use App\Support\MediaUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use JsonException;

class HomepageController extends Controller
{
    public function edit(): View
    {
        $this->authorize('viewAny', HomepageSection::class);

        $sections = HomepageSection::query()
            ->orderBy('sort_order')
            ->orderBy('key')
            ->get();

        return view('admin.homepage.edit', compact('sections'));
    }

    public function update(HomepageSectionRequest $request, HomepageSection $section): RedirectResponse
    {
        $this->authorize('update', $section);

        $data = $request->validated();
        $config = $section->config ?? [];

        if (filled($data['config_json'] ?? null)) {
            try {
                $decoded = json_decode($data['config_json'], true, 512, JSON_THROW_ON_ERROR);
                if (! is_array($decoded)) {
                    return back()->withInput()->withErrors(['config_json' => 'Config must be a JSON object.']);
                }
                $config = $decoded;
            } catch (JsonException) {
                return back()->withInput()->withErrors(['config_json' => 'Invalid JSON.']);
            }
        }

        // Association-only: clearing image_path does not delete shared media files.
        $imagePath = array_key_exists('image_path', $data)
            ? MediaUrl::normalize($data['image_path'] ?? null)
            : $section->image_path;

        if ($request->boolean('remove_image')) {
            $imagePath = null;
        }

        $section->fill([
            'title' => $data['title'] ?? null,
            'subtitle' => $data['subtitle'] ?? null,
            'body' => $data['body'] ?? null,
            'cta_label' => $data['cta_label'] ?? null,
            'cta_url' => $data['cta_url'] ?? null,
            'image_path' => $imagePath,
            'is_enabled' => (bool) ($data['is_enabled'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'config' => $config,
        ])->save();

        return redirect()
            ->route('admin.homepage.edit')
            ->with('flash_success', $section->label().' section saved.');
    }
}
