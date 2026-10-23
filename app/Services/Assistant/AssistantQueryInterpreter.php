<?php

namespace App\Services\Assistant;

class AssistantQueryInterpreter
{
    public function __construct(private readonly AssistantTextNormalizer $normalizer) {}

    /** Extract catalog/search terms without treating question wording as ingredients. */
    public function terms(string $message): string
    {
        $text = $this->normalizer->normalize($message);
        $text = preg_replace('/^i am not pregnant[.!]\s*(?=what is .*price|how much .*cost)/u', '', $text);
        $text = preg_replace('/\bco\s*q\s*10\b|\bcoqten\b/u', 'coq10', $text);
        $text = strtr($text, ['وٹامن' => 'vitamin', 'مجھے' => '', 'چاہیے' => '', 'vitamins' => 'vitamin', 'minerals' => 'mineral', 'supplements' => 'supplement', 'magneshium' => 'magnesium', 'میگنیشیم' => 'magnesium', 'मैग्नीशियम' => 'magnesium']);
        $stop = ['where', 'something', 'recommend', 'recommendation', 'health', 'aapke', 'kaunse', 'ki', 'kitne', 'ka', 'hi', 'hello', 'hey', 'what', 'which', 'kind', 'kinds', 'type', 'types', 'of', 'a', 'an', 'the', 'do', 'does', 'you', 'your', 'have', 'has', 'got', 'are', 'is', 'there', 'any', 'some', 'can', 'could', 'would', 'will', 'i', 'we', 'me', 'my', 'please', 'show', 'tell', 'about', 'looking', 'look', 'find', 'for', 'need', 'want', 'like', 'to', 'see', 'with', 'related', 'product', 'products', 'range', 'collection', 'catalog', 'sell', 'selling', 'stock', 'in', 'available', 'availability', 'price', 'cost', 'much', 'how', 'check', 'offer', 'offering', 'options', 'option', 'browse', 'explore', 'all', 'list', 'ingredients', 'ingredient', 'directions', 'use', 'articles', 'article', 'blogs', 'blog', 'read', 'learn', 'information', 'buy', 'and', 'or', 'help', 'mujhe', 'chahiye', 'hain', 'hai', 'kya', 'aap', 'ke', 'paas'];

        return implode(' ', array_values(array_filter($this->normalizer->tokens($text), fn ($token) => ! in_array($token, $stop, true))));
    }
}
