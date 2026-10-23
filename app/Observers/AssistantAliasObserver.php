<?php

namespace App\Observers;

use App\Models\AssistantAlias;
use App\Services\Assistant\AssistantKnowledgeSync;

class AssistantAliasObserver
{
    public function __construct(
        private readonly AssistantKnowledgeSync $sync,
    ) {}

    public function saved(AssistantAlias $alias): void
    {
        $this->sync->queueAliasTouch($alias);
    }

    public function deleted(AssistantAlias $alias): void
    {
        $this->sync->queueAliasTouch($alias);
    }
}
