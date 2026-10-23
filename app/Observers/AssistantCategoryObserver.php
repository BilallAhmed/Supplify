<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\Assistant\AssistantKnowledgeSync;

class AssistantCategoryObserver
{
    public function __construct(
        private readonly AssistantKnowledgeSync $sync,
    ) {}

    public function saved(Category $category): void
    {
        $this->sync->queueProductsForCategory($category);
    }

    public function deleted(Category $category): void
    {
        $this->sync->queueProductsForCategory($category);
    }

    public function restored(Category $category): void
    {
        $this->sync->queueProductsForCategory($category);
    }
}
