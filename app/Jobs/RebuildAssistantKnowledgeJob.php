<?php

namespace App\Jobs;

use App\Services\Assistant\AssistantKnowledgeIndexer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

class RebuildAssistantKnowledgeJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 1;

    public function __construct()
    {
        $this->onQueue('assistant');
    }

    public function handle(AssistantKnowledgeIndexer $indexer): void
    {
        $indexer->rebuildAll();
    }

    public function failed(?Throwable $exception): void
    {
        try {
            app(AssistantKnowledgeIndexer::class)->recordFailure(
                'rebuild',
                $exception?->getMessage() ?? 'Unknown failure',
            );
        } catch (Throwable) {
            //
        }
    }
}
