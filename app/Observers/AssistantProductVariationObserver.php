<?php

namespace App\Observers;

use App\Models\ProductVariation;
use App\Services\Assistant\AssistantKnowledgeSync;

class AssistantProductVariationObserver
{
    public function __construct(
        private readonly AssistantKnowledgeSync $sync,
    ) {}

    public function saved(ProductVariation $variation): void
    {
        $this->sync->queueProduct((int) $variation->product_id);
    }

    public function deleted(ProductVariation $variation): void
    {
        $this->sync->queueProduct((int) $variation->product_id);
    }
}
