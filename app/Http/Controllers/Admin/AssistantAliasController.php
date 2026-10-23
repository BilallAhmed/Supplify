<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AssistantIntent;
use App\Enums\AssistantSourceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssistantAliasRequest;
use App\Models\AssistantAlias;
use App\Services\Assistant\AssistantTextNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssistantAliasController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AssistantAlias::class);

        $aliases = AssistantAlias::query()
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('phrase', 'like', $term)
                        ->orWhere('normalized_phrase', 'like', $term);
                });
            })
            ->when($request->filled('locale'), fn ($q) => $q->where('locale', $request->string('locale')))
            ->when($request->input('status') === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->input('status') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('locale')
            ->orderBy('phrase')
            ->paginate(20)
            ->withQueryString();

        return view('admin.assistant.aliases.index', compact('aliases'));
    }

    public function create(): View
    {
        $this->authorize('create', AssistantAlias::class);

        return view('admin.assistant.aliases.create', [
            'alias' => new AssistantAlias([
                'locale' => 'en',
                'is_active' => true,
            ]),
            'intents' => AssistantIntent::cases(),
            'sourceTypes' => AssistantSourceType::cases(),
        ]);
    }

    public function store(AssistantAliasRequest $request, AssistantTextNormalizer $normalizer): RedirectResponse
    {
        $attrs = $request->aliasAttributes();
        $attrs['normalized_phrase'] = $normalizer->normalize($attrs['phrase']);

        AssistantAlias::query()->create($attrs);

        return redirect()
            ->route('admin.assistant.aliases.index')
            ->with('flash_success', 'Alias created.');
    }

    public function edit(AssistantAlias $alias): View
    {
        $this->authorize('update', $alias);

        return view('admin.assistant.aliases.edit', [
            'alias' => $alias,
            'intents' => AssistantIntent::cases(),
            'sourceTypes' => AssistantSourceType::cases(),
        ]);
    }

    public function update(AssistantAliasRequest $request, AssistantAlias $alias, AssistantTextNormalizer $normalizer): RedirectResponse
    {
        $attrs = $request->aliasAttributes();
        $attrs['normalized_phrase'] = $normalizer->normalize($attrs['phrase']);

        $alias->update($attrs);

        return redirect()
            ->route('admin.assistant.aliases.index')
            ->with('flash_success', 'Alias updated.');
    }

    public function destroy(AssistantAlias $alias): RedirectResponse
    {
        $this->authorize('delete', $alias);
        $alias->delete();

        return redirect()
            ->route('admin.assistant.aliases.index')
            ->with('flash_success', 'Alias deleted.');
    }
}
