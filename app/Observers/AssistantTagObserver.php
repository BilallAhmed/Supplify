<?php

namespace App\Observers;

use App\Models\Tag;
use App\Services\Assistant\AssistantKnowledgeSync;

class AssistantTagObserver
{
    public function __construct(
        private readonly AssistantKnowledgeSync $sync,
    ) {}

    public function saved(Tag $tag): void
    {
        $this->sync->queueSourcesForTag($tag);
    }

    public function deleted(Tag $tag): void
    {
        $this->sync->queueSourcesForTag($tag);
    }
}
