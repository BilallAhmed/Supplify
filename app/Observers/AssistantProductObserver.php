<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\Assistant\AssistantKnowledgeSync;

class AssistantProductObserver
{
    public function __construct(
        private readonly AssistantKnowledgeSync $sync,
    ) {}

    public function saved(Product $product): void
    {
        $this->sync->queueProduct((int) $product->id);
    }

    public function deleted(Product $product): void
    {
        $this->sync->queueProduct((int) $product->id);
    }

    public function restored(Product $product): void
    {
        $this->sync->queueProduct((int) $product->id);
    }

    public function forceDeleted(Product $product): void
    {
        $this->sync->queueProduct((int) $product->id);
    }
}
