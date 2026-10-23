<?php

namespace App\Observers;

use App\Enums\AssistantSourceType;
use App\Models\AssistantFaq;
use App\Services\Assistant\AssistantKnowledgeSync;

class AssistantFaqObserver
{
    public function __construct(
        private readonly AssistantKnowledgeSync $sync,
    ) {}

    public function saved(AssistantFaq $faq): void
    {
        $this->sync->queueSource(AssistantSourceType::Faq, (int) $faq->id);
    }

    public function deleted(AssistantFaq $faq): void
    {
        $this->sync->queueSource(AssistantSourceType::Faq, (int) $faq->id);
    }

    public function restored(AssistantFaq $faq): void
    {
        $this->sync->queueSource(AssistantSourceType::Faq, (int) $faq->id);
    }
}
