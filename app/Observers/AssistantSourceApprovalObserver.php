<?php

namespace App\Observers;

use App\Enums\AssistantSourceType;
use App\Models\AssistantSourceApproval;
use App\Services\Assistant\AssistantKnowledgeSync;

class AssistantSourceApprovalObserver
{
    public function __construct(
        private readonly AssistantKnowledgeSync $sync,
    ) {}

    public function saved(AssistantSourceApproval $approval): void
    {
        $this->sync->queueSource($approval->source_type, (int) $approval->source_id);
    }

    public function deleted(AssistantSourceApproval $approval): void
    {
        $type = $approval->source_type instanceof AssistantSourceType
            ? $approval->source_type
            : AssistantSourceType::from((string) $approval->source_type);

        $this->sync->queueSource($type, (int) $approval->source_id);
    }
}
