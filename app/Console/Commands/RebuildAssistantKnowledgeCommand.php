<?php

namespace App\Console\Commands;

use App\Jobs\RebuildAssistantKnowledgeJob;
use App\Services\Assistant\AssistantKnowledgeIndexer;
use Illuminate\Console\Command;

class RebuildAssistantKnowledgeCommand extends Command
{
    protected $signature = 'assistant:rebuild-knowledge {--sync : Run inline instead of queueing}';

    protected $description = 'Rebuild Supplify Assistant searchable knowledge from approved eligible sources';

    public function handle(AssistantKnowledgeIndexer $indexer): int
    {
        if ($this->option('sync')) {
            $counts = $indexer->rebuildAll();
            $this->info('Assistant knowledge rebuild complete.');
            $this->table(array_keys($counts), [array_values($counts)]);

            return self::SUCCESS;
        }

        RebuildAssistantKnowledgeJob::dispatch();
        $this->info('Assistant knowledge rebuild queued.');

        return self::SUCCESS;
    }
}
