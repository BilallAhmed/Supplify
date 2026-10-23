<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssistantRebuildRequest;
use App\Jobs\RebuildAssistantKnowledgeJob;
use App\Services\Assistant\AssistantKnowledgeIndexer;
use Illuminate\Http\RedirectResponse;

class AssistantRebuildController extends Controller
{
    public function __invoke(
        AssistantRebuildRequest $request,
        AssistantKnowledgeIndexer $indexer,
    ): RedirectResponse {
        if ($request->boolean('sync')) {
            $counts = $indexer->rebuildAll();

            return redirect()
                ->route('admin.assistant.edit')
                ->with('status', sprintf(
                    'Knowledge rebuilt inline. Indexed: %d. Removed: %d. Errors: %d.',
                    $counts['indexed'] ?? 0,
                    $counts['removed'] ?? 0,
                    $counts['errors'] ?? 0,
                ));
        }

        RebuildAssistantKnowledgeJob::dispatch();

        return redirect()
            ->route('admin.assistant.edit')
            ->with('status', 'Knowledge rebuild queued. Ensure a queue worker is running.');
    }
}
