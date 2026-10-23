<?php

namespace Tests\Unit\Assistant;

use App\Services\Assistant\AssistantTextNormalizer;
use PHPUnit\Framework\TestCase;

class AssistantTextNormalizerTest extends TestCase
{
    public function test_phrase_match_keeps_vitamin_forms_distinct(): void
    {
        $n = new AssistantTextNormalizer;

        $this->assertTrue($n->containsPhrase('Vitamin D3 25 mcg', 'Vitamin D3'));
        $this->assertFalse($n->containsPhrase('Vitamin E 15 mg', 'Vitamin D3'));
        $this->assertFalse($n->containsPhrase('Vitamin E 15 mg', 'vitamin d3'));
        $this->assertTrue($n->containsPhrase('Vitamin E 15 mg', 'Vitamin E'));
    }
}
