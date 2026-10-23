<?php

namespace App\Services\Assistant;

use App\Enums\AssistantIntent;
use App\Enums\AssistantResponseType;
use App\Enums\AssistantSourceType;
use App\Models\AssistantSourceApproval;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Support\Assistant\AssistantChoice;
use App\Support\Assistant\AssistantResponse;
use App\Support\Assistant\AssistantSourceReference;
use Illuminate\Database\Eloquent\Builder;

/** Guided conversation using live approved website data; no model or external API. */
class AssistantDiscovery
{
    public function __construct(
        private readonly AssistantQueryInterpreter $interpreter,
        private readonly AssistantTextNormalizer $normalizer,
        private readonly AssistantEligibility $eligibility,
        private readonly AssistantCatalogResolver $catalog,
        private readonly AssistantResponseBuilder $builder,
        private readonly AssistantConversationState $state,
        private readonly AssistantKnowledgeSearch $search,
    ) {}

    public function respond(string $message): ?AssistantResponse
    {
        $plain = implode(' ', $this->normalizer->tokens($message));
        if (preg_match('/^(what can you do|how can you help|help|help me)$/u', $plain)) {
            return $this->overview();
        }
        $savedTopic = $this->state->get()['discovery_topic'] ?? '';
        if ($savedTopic && in_array($plain, ['yes', 'yes please', 'products', 'show products', 'product'], true)) {
            $result = $this->builder->fromSearch($this->search->search($savedTopic));

            return $result->products !== [] ? $result : ($this->topic($savedTopic, true) ?? $this->fallback());
        }
        if (preg_match('/^(hi|hello|hey|salam|assalam o alaikum|assalamualaikum|السلام علیکم)( there)?$/u', $plain)) {
            return $this->reply('Hi! I’m Supplify Assistant. I can help you explore our range, check prices and stock, or find useful reading. What would you like to explore?', $this->browseChoices());
        }
        if (preg_match('/^(thanks|thank you|thankyou|shukriya|شکریہ)( so much)?$/u', $plain)) {
            return $this->reply('You’re welcome! Is there another product or question I can help with?', $this->currentChoices());
        }
        if (in_array($plain, ['yes', 'yes please', 'sure', 'okay', 'ok', 'ji', 'haan'], true)) {
            $selected = $this->state->selectedProductId();
            $product = $selected ? $this->catalog->freshProduct($selected) : null;

            return $this->reply($product ? 'Sure—what would you like to know about '.$product->name.'?' : 'Sure—would you like to explore products, vitamins, or minerals?', $this->currentChoices());
        }
        if (in_array($plain, ['no', 'no thanks', 'not now', 'nahi'], true)) {
            $this->state->setTopic(null);

            return $this->reply('No problem. You can explore the range or ask about a different product whenever you’re ready.', $this->browseChoices());
        }
        $topic = $this->interpreter->terms($message);
        if ($topic === '' || in_array($topic, ['supplement', 'supplement store', 'store'], true)) {
            if (preg_match('/product|supplement|range|catalog|collection|sell|offer|help me find/i', $message)) {
                return $this->overview();
            }
        }
        if (in_array($topic, ['vitamin', 'mineral'], true)) {
            return $this->browse($topic === 'vitamin' ? 'vitamins' : 'minerals');
        }
        $goal = trim(preg_replace('/\b(support|boost|essentials?)\b/u', '', $topic));
        foreach (config('assistant.search.wellness_goals', []) as $key => $phrases) {
            if (in_array($goal, array_merge([$key], $phrases), true)) {
                return $this->browse($key);
            }
        }

        return null;
    }

    public function overview(): AssistantResponse
    {
        $labels = Category::query()->where('is_active', true)->orderBy('name')->limit(20)->get()
            ->filter(fn ($category) => $this->eligibility->isCategoryEligible($category))->pluck('name')->all();
        $hasProducts = Product::query()->published()->whereIn('id', $this->approvedIds('product'))->whereDoesntHave('tags', fn ($q) => $q->where('slug', 'editorial-draft'))->exists();
        $intro = $hasProducts ? 'I can help you explore our nutritional supplement range.' : 'I don’t have product listings to share right now, but I can help you find website information or contact the team.';
        if ($labels !== []) {
            $intro .= ' You can explore '.implode(', ', $labels).'.';
        }
        $intro .= ' Would you like to browse vitamins, minerals, or see a few products from the range?';
        $this->state->setTopic(null);

        return new AssistantResponse(type: AssistantResponseType::Answer, intent: AssistantIntent::FindProduct, answer: $intro, followUps: $this->browseChoices());
    }

    public function browse(string $key, int $after = 0): AssistantResponse
    {
        $allowed = ['all', 'vitamins', 'minerals', ...array_keys(config('assistant.search.wellness_goals', []))];
        if (! in_array($key, $allowed, true)) {
            return $this->overview();
        }
        $this->state->setTopic(null);
        $query = Product::query()->published()->with(['tags', 'categories'])
            ->whereIn('id', $this->approvedIds('product'))
            ->whereDoesntHave('tags', fn ($q) => $q->where('slug', 'editorial-draft'));
        if ($key === 'vitamins') {
            $query->where(fn ($q) => $q->where('name', 'like', '%vitamin%')->orWhere('short_description', 'like', '%vitamin%'));
        } elseif ($key === 'minerals') {
            $query->where(function ($q) {
                foreach (['magnesium', 'calcium', 'zinc', 'iron', 'selenium', 'potassium'] as $word) {
                    $q->orWhere('name', 'like', '%'.$word.'%');
                }
            });
        } elseif ($key !== 'all') {
            $query->whereHas('categories', fn ($q) => $q->where('slug', 'like', '%'.$key.'%')->whereIn('categories.id', $this->approvedIds('category')));
        }
        $limit = (int) config('assistant.limits.max_products_per_answer', 3);
        $records = $query->where('id', '>', max(0, $after))->orderBy('id')->limit($limit + 1)->get();
        $hasMore = $records->count() > $limit;
        $products = [];
        $sources = [];
        $choices = [];
        foreach ($records->take($limit) as $record) {
            $card = $this->catalog->productCard($record->id);
            $source = $this->catalog->productSource($record->id);
            if (! $card || ! $source) {
                continue;
            }
            $products[] = $card;
            $sources[] = $source;
            $choices[] = new AssistantChoice('product:'.$record->id, $record->name, AssistantIntent::FindProduct, (string) $record->id);
        }
        $label = match ($key) {
            'all' => 'our range', 'vitamins' => 'vitamins', 'minerals' => 'minerals', default => str_replace('_', ' ', $key).' products'
        };
        $answer = $products !== [] ? 'Here are '.($after ? 'a few more options from ' : 'a few options from ').$label.'. Pick a product and I can check its price, stock, or label information. Which one would you like to explore?' : 'I don’t have a product to show in '.$label.' right now. Would you like to explore another part of the range?';
        if ($hasMore && $products !== []) {
            $choices[] = new AssistantChoice('browse', 'Show more products', AssistantIntent::FindProduct, $key.':'.end($products)->id);
        }
        $choices[] = new AssistantChoice('overview', 'Explore another category', AssistantIntent::FindProduct, 'overview');

        return new AssistantResponse(type: $products !== [] ? AssistantResponseType::Clarification : AssistantResponseType::Answer, intent: AssistantIntent::FindProduct, answer: $answer, sources: $sources, products: $products, followUps: $choices);
    }

    /** Recover meaningful approved article mentions even without a matching product. */
    public function topic(string $message, bool $productsOnly = false): ?AssistantResponse
    {
        $topic = $this->interpreter->terms($message);
        $tokens = $this->normalizer->tokens($topic);
        if ($topic === '' || count($tokens) > 6 || mb_strlen($topic) < 3) {
            return null;
        }
        $query = Post::query()->published()->whereIn('id', $this->approvedIds('post'));
        foreach ($tokens as $token) {
            $like = '%'.addcslashes($token, '%_\\').'%';
            $query->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('excerpt', 'like', $like)->orWhere('body', 'like', $like));
        }
        $contents = [];
        $sources = [];
        foreach ($query->latest('published_at')->limit(12)->get() as $post) {
            if (! $this->normalizer->containsPhrase($post->title.' '.$post->excerpt.' '.strip_tags($post->body ?? ''), $topic)) {
                continue;
            }
            $card = $this->catalog->postCard($post->id);
            if (! $card) {
                continue;
            }
            $contents[] = $card;
            $sources[] = new AssistantSourceReference(AssistantSourceType::Post, $card->id, $card->title, $card->url);
            if (count($contents) >= (int) config('assistant.limits.max_articles_per_answer', 2)) {
                break;
            }
        }
        if ($contents === []) {
            return null;
        }
        $this->state->setTopic($topic);
        $label = ucfirst($topic);
        $answer = $productsOnly ? 'I can’t confirm a '.$topic.' product from the product information currently available to me. This guide mentions '.$topic.' and may help you explore the topic. Would you like to read it or browse another category?' : 'Looking into '.$topic.'? I found '.(count($contents) === 1 ? 'a guide that mentions it' : 'these guides that mention it').'. Would you like to read more, look for a product, or explore the range?';

        return new AssistantResponse(type: AssistantResponseType::Answer, intent: AssistantIntent::RelatedArticles, answer: $answer, sources: $sources, contents: $contents, followUps: [...($productsOnly ? [] : [new AssistantChoice('topic-products', $label.' products', AssistantIntent::FindProduct, 'topic-products')]), ...$this->browseChoices()]);
    }

    public function fallback(): AssistantResponse
    {
        return new AssistantResponse(type: AssistantResponseType::Unknown, intent: AssistantIntent::Unknown, answer: 'I couldn’t find a reliable match for that yet. Could you share the product or ingredient name? You can also browse the range below, or contact our team for help.', followUps: $this->browseChoices());
    }

    private function approvedIds(string $type): Builder
    {
        return AssistantSourceApproval::query()->select('source_id')->where('source_type', $type)->whereNotNull('approved_at');
    }

    private function reply(string $answer, array $choices): AssistantResponse
    {
        return new AssistantResponse(type: AssistantResponseType::Answer, intent: AssistantIntent::Unknown, answer: $answer, followUps: $choices);
    }

    private function currentChoices(): array
    {
        $id = $this->state->selectedProductId();

        return $id && $this->catalog->freshProduct($id) ? $this->builder->productFollowUps($id) : $this->browseChoices();
    }

    private function browseChoices(): array
    {
        return [new AssistantChoice('browse', 'Browse products', AssistantIntent::FindProduct, 'all'), new AssistantChoice('browse-vitamins', 'Vitamins', AssistantIntent::FindProduct, 'vitamins'), new AssistantChoice('browse-minerals', 'Minerals', AssistantIntent::FindProduct, 'minerals'), new AssistantChoice('contact', 'Contact the team', AssistantIntent::DeliveryReturnsContact, 'contact')];
    }
}
