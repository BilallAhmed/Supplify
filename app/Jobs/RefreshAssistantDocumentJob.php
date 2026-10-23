<?php

namespace App\Jobs;

use App\Enums\AssistantSourceType;
use App\Services\Assistant\AssistantKnowledgeIndexer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RefreshAssistantDocumentJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public string $sourceType,
        public int $sourceId,
        public ?string $dispatchedRevision = null,
        public ?string $dispatchedAt = null,
    ) {
        $this->onQueue('assistant');
    }

    public function handle(AssistantKnowledgeIndexer $indexer): void
    {
        $type = AssistantSourceType::from($this->sourceType);

        $indexer->refreshIfCurrent(
            $type,
            $this->sourceId,
            $this->dispatchedRevision,
            $this->dispatchedAt,
        );
    }

    public function failed(?Throwable $exception): void
    {
        try {
            app(AssistantKnowledgeIndexer::class)->recordFailure(
                sprintf('refresh:%s:%d', $this->sourceType, $this->sourceId),
                $exception?->getMessage() ?? 'Unknown failure',
            );
        } catch (Throwable) {
            // Avoid cascading failures from status persistence.
        }
    }
}
