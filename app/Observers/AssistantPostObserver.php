<?php

namespace App\Observers;

use App\Enums\AssistantSourceType;
use App\Models\Post;
use App\Services\Assistant\AssistantKnowledgeSync;

class AssistantPostObserver
{
    public function __construct(
        private readonly AssistantKnowledgeSync $sync,
    ) {}

    public function saved(Post $post): void
    {
        $this->sync->queueSource(AssistantSourceType::Post, (int) $post->id);
    }

    public function deleted(Post $post): void
    {
        $this->sync->queueSource(AssistantSourceType::Post, (int) $post->id);
    }

    public function restored(Post $post): void
    {
        $this->sync->queueSource(AssistantSourceType::Post, (int) $post->id);
    }

    public function forceDeleted(Post $post): void
    {
        $this->sync->queueSource(AssistantSourceType::Post, (int) $post->id);
    }
}
