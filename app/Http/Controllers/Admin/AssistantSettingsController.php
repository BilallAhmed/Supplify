<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssistantSettingsUpdateRequest;
use App\Models\AssistantAlias;
use App\Models\AssistantFaq;
use App\Services\Assistant\AssistantEligibility;
use App\Services\Assistant\AssistantKnowledgeIndexer;
use App\Services\Assistant\AssistantKnowledgeSearch;
use App\Services\Assistant\AssistantSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AssistantSettingsController extends Controller
{
    public function edit(
        AssistantSettings $settings,
        AssistantEligibility $eligibility,
        AssistantKnowledgeIndexer $indexer,
        AssistantKnowledgeSearch $search,
    ): View {
        $this->authorize('manageSettings', AssistantFaq::class);

        return view('admin.assistant.edit', [
            'settings' => $settings,
            'configEnabled' => (bool) config('assistant.enabled', false),
            'runtimeEnabled' => (bool) app(\App\Services\StoreSettings::class)->get('assistant.enabled', false),
            'effectivelyEnabled' => $settings->isEnabled(),
            'readableFields' => config('assistant.readable_fields', []),
            'prohibitedFields' => $eligibility->prohibitedFieldPatterns(),
            'limits' => $settings->limits(),
            'syncStatus' => $indexer->status(),
            'searchDriver' => $search->driverName(),
            'faqCount' => AssistantFaq::query()->count(),
            'aliasCount' => AssistantAlias::query()->count(),
        ]);
    }

    public function update(AssistantSettingsUpdateRequest $request, AssistantSettings $settings): RedirectResponse
    {
        $settings->setEnabled($request->boolean('enabled'));

        return redirect()
            ->route('admin.assistant.edit')
            ->with('status', 'Assistant settings saved. The public widget requires ASSISTANT_ENABLED=true and runtime enable both on.');
    }
}
