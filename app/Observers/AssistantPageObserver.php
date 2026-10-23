<?php

namespace App\Observers;

use App\Enums\AssistantSourceType;
use App\Models\Page;
use App\Services\Assistant\AssistantKnowledgeSync;

class AssistantPageObserver
{
    public function __construct(
        private readonly AssistantKnowledgeSync $sync,
    ) {}

    public function saved(Page $page): void
    {
        $this->sync->queueSource(AssistantSourceType::Page, (int) $page->id);
    }

    public function deleted(Page $page): void
    {
        $this->sync->queueSource(AssistantSourceType::Page, (int) $page->id);
    }

    public function restored(Page $page): void
    {
        $this->sync->queueSource(AssistantSourceType::Page, (int) $page->id);
    }

    public function forceDeleted(Page $page): void
    {
        $this->sync->queueSource(AssistantSourceType::Page, (int) $page->id);
    }
}
