<?php

namespace App\Services\Assistant;

use App\Enums\AssistantIntent;
use App\Enums\AssistantResponseType;
use App\Enums\AssistantSourceType;
use App\Models\AssistantFaq;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Support\Assistant\AssistantContentCard;
use App\Support\Assistant\AssistantResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssistantConversationService
{
    public function __construct(
        private readonly AssistantConversationState $state,
        private readonly AssistantKnowledgeSearch $search,
        private readonly AssistantResponseBuilder $builder,
        private readonly AssistantCatalogResolver $catalog,
        private readonly AssistantSafetyRules $safety,
        private readonly AssistantTextNormalizer $normalizer,
        private readonly AssistantIntentRouter $intents,
        private readonly AssistantSettings $settings,
        private readonly AssistantDiscovery $discovery,
        private readonly AssistantQueryInterpreter $interpreter,
        private readonly AssistantMedicalGuard $medical,
        private readonly AssistantFactAnswers $facts,
    ) {}

    /**
     * @return array{response: AssistantResponse, state: array<string, mixed>}
     */
    public function handle(string $message, ?string $choiceId = null, ?string $choicePayload = null): array
    {
        $clean = $this->safety->sanitizeUserMessage($message);
        if ($this->medical->classify($clean) === 'urgent') {
            $this->state->setSelectedProductId(null);
            $this->state->setOffered([], [], '');
            $this->state->setTopic(null);

            return $this->finish(AssistantResponse::boundary('This may need urgent medical attention. Please seek immediate help from local emergency services or an emergency department. Do not wait for this store assistant to assess symptoms or an overdose.'));
        }
        $maxTurns = $this->settings->limit('max_session_turns', 20);
        if ($this->state->turnCount() >= $maxTurns) {
            return $this->finish(
                AssistantResponse::unknown('This assistant session reached its turn limit. Reset the chat to continue.')
            );
        }

        $this->state->incrementTurn();
        if (($clean === '' || mb_strlen($clean) < 2) && $choiceId === null && $choicePayload === null) {
            return $this->finish(AssistantResponse::unknown(
                'Please enter at least two characters so Supplify Assistant can search the website.'
            ));
        }

        if ($this->safety->looksLikeInstructionInjection($clean)) {
            return $this->finish($this->safety->injectionResponse());
        }

        $normalized = $this->normalizer->normalize($clean);
        if ($this->intents->route($clean) === AssistantIntent::Boundary) {
            $this->state->setSelectedProductId(null);
            $this->state->setOffered([], [], '');
            $this->state->setTopic(null);

            return $this->finish($this->safety->boundaryResponse());
        }
        if ($choiceId !== null || $choicePayload !== null) {
            return $this->finish($this->handleChoice($choiceId, $choicePayload));
        }
        $factual = $this->facts->respond($clean);
        if ($factual !== null) {
            $this->rememberFromResponse($factual, $clean, $factual->intent);

            return $this->finish($factual);
        }
        $guided = $this->discovery->respond($clean);
        if ($guided !== null) {
            if ($guided->intent !== AssistantIntent::Unknown) {
                $this->rememberFromResponse($guided, $clean, $guided->intent);
            }

            return $this->finish($guided);
        }
        $followUp = $this->detectFollowUp($normalized);
        $named = $this->namedProduct($normalized);
        if ($named !== null) {
            $this->state->setSelectedProductId((int) $named->id);
            $this->state->setOffered([(int) $named->id], [], $clean);
        }

        if ($followUp !== null && ($named !== null || $this->isContextualQuestion($normalized))) {
            $response = $this->handleFollowUp($followUp, $clean);
            if ($response !== null) {
                return $this->finish($response);
            }
        }

        if ($followUp !== null && $this->isContextualQuestion($normalized) && $this->state->selectedProductId() === null && $this->state->offeredProductIds() === []) {
            return $this->finish(new AssistantResponse(
                type: AssistantResponseType::Clarification,
                intent: AssistantIntent::Clarify,
                answer: 'Which product would you like to check? Please enter its name so I can look up the current website information.',
            ));
        }
        $lookup = $this->interpreter->terms($clean);
        $searchQuery = in_array($this->intents->route($clean), [AssistantIntent::FindProduct, AssistantIntent::IngredientsOrDirections, AssistantIntent::PriceOrAvailability, AssistantIntent::RelatedArticles], true) && $lookup !== '' ? $lookup : $clean;
        if ($this->intents->route($clean) === AssistantIntent::RelatedArticles) {
            $searchQuery = $clean;
        }
        $outcome = $this->search->search($searchQuery);
        $response = $this->builder->fromSearch($outcome);
        if ($response->type === AssistantResponseType::Unknown) {
            $response = $this->discovery->topic($clean) ?? $this->discovery->fallback();
        }
        if ($followUp !== null && count($response->products) === 1 && $response->type === AssistantResponseType::Answer) {
            $this->state->setSelectedProductId($response->products[0]->id);
            $response = $this->handleFollowUp($followUp, $clean) ?? $response;
        }

        // An unrelated aside does not discard the selected product. An explicit
        // new product/ingredient query does, even if that product is unavailable.
        $aside = $response->type === AssistantResponseType::Unknown
            && preg_match('/\b(weather|poem|president|laptop|football|joke)\b/i', $clean);
        if (! $aside) {
            $this->rememberFromResponse($response, $clean, $outcome->intent);
        }

        return $this->finish($response);
    }

    public function reset(): void
    {
        $this->state->reset();
        session()->forget('assistant.transcript');
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return $this->state->get();
    }

    private function handleChoice(?string $choiceId, ?string $choicePayload): AssistantResponse
    {
        if (in_array($choiceId, ['browse', 'browse-vitamins', 'browse-minerals'], true)) {
            if (! preg_match('/^(all|vitamins|minerals|energy|immunity|gut|sleep)(?::(\d{1,10}))?$/', (string) $choicePayload, $match)) {
                return $this->discovery->fallback();
            }
            $reply = $this->discovery->browse($match[1], (int) ($match[2] ?? 0));
            $this->rememberFromResponse($reply, $match[1], $reply->intent);

            return $reply;
        }
        if ($choiceId === 'overview') {
            $reply = $this->discovery->overview();
            $this->rememberFromResponse($reply, '', $reply->intent);

            return $reply;
        }
        if ($choiceId === 'topic-products') {
            $topic = $this->state->get()['discovery_topic'] ?? '';
            if ($topic === '') {
                return $this->discovery->overview();
            }
            $result = $this->builder->fromSearch($this->search->search($topic));
            if ($result->products === []) {
                $result = $this->discovery->topic($topic, true) ?? $this->discovery->fallback();
            }
            $this->rememberFromResponse($result, $topic, $result->intent);

            return $result;
        }
        $payload = trim((string) ($choicePayload ?: Str::after((string) $choiceId, ':')));
        if ($payload === '' || $payload === (string) $choiceId) {
            $payload = trim((string) $choicePayload);
        }
        if ($payload === 'contact') {
            $page = Page::query()->where('slug', 'contact')->first();

            return new AssistantResponse(
                type: AssistantResponseType::Answer,
                intent: AssistantIntent::DeliveryReturnsContact,
                answer: 'You can reach Supplify through the Contact page on this website.',
                contents: array_values(array_filter([
                    $page ? $this->catalog->pageCard((int) $page->id) : null,
                ])),
                followUps: $this->builder->contactFollowUps(),
            );
        }

        if (ctype_digit($payload) && in_array($choiceId, ['price', 'stock', 'ingredients', 'directions', 'articles'], true)) {
            $productId = (int) $payload;
            if ($productId !== $this->state->selectedProductId() && ! in_array($productId, $this->state->offeredProductIds(), true)) {
                return AssistantResponse::unknown('That option has expired. Please search for the product again.');
            }
            $this->state->setSelectedProductId($productId);

            return $this->handleFollowUp($choiceId, '') ?? AssistantResponse::unknown('Please search for a product first.');
        }
        if (ctype_digit($payload) && str_starts_with((string) $choiceId, 'product:')) {
            $productId = (int) $payload;
            if (! in_array($productId, $this->state->offeredProductIds(), true)) {
                return AssistantResponse::unknown('That option has expired. Please search again.');
            }
            $this->state->setSelectedProductId($productId);
            $this->state->setIntent(AssistantIntent::FindProduct);
            $product = $this->catalog->freshProduct($productId);
            if (! $product) {
                return AssistantResponse::unknown('That product is no longer available in approved website listings.');
            }
            $card = $this->catalog->productCard($productId);

            return new AssistantResponse(
                type: AssistantResponseType::Answer,
                intent: AssistantIntent::FindProduct,
                answer: sprintf('Let’s take a closer look at %s. Would you like to check its price, stock, or label information?', $product->name),
                products: $card ? [$card] : [],
                sources: array_filter([$this->catalog->productSource($productId)]),
                followUps: $this->builder->productFollowUps($productId),
            );
        }

        if (preg_match('/^(post|page|category|faq):(\d+)$/', (string) $choiceId, $match)) {
            $key = $match[1].':'.$match[2];
            if (in_array($key, $this->state->get()['offered_source_ids'], true)) {
                $type = AssistantSourceType::from($match[1]);
                $model = match ($type) {
                    AssistantSourceType::Post => Post::find($match[2]),
                    AssistantSourceType::Page => Page::find($match[2]),
                    AssistantSourceType::Category => Category::find($match[2]),
                    AssistantSourceType::Faq => AssistantFaq::find($match[2]),
                    default => null,
                };
                if ($model) {
                    $query = $model->title ?? $model->name ?? $model->question;

                    return $this->builder->fromSearch($this->search->search($query));
                }
            }
        }

        return AssistantResponse::unknown('That choice was not recognized. Try searching again.');
    }

    private function handleFollowUp(string $followUp, string $originalMessage): ?AssistantResponse
    {
        $selected = $this->state->selectedProductId();
        $offered = $this->state->offeredProductIds();

        if ($selected === null && count($offered) > 1) {
            return $this->builder->whichProduct($offered);
        }

        if ($selected === null && count($offered) === 1) {
            $selected = $offered[0];
            $this->state->setSelectedProductId($selected);
        }

        if ($selected === null) {
            // No context — fall through to fresh search.
            return null;
        }

        $product = $this->catalog->freshProduct($selected);
        if (! $product) {
            $this->state->setSelectedProductId(null);

            return AssistantResponse::unknown('That product is no longer available. Try searching for it again.');
        }

        return match ($followUp) {
            'price' => $this->builder->priceFor($product),
            'stock' => $this->builder->stockFor($product),
            'ingredients' => $this->builder->ingredientsFor($product),
            'directions' => $this->builder->directionsFor($product),
            'articles' => $this->builder->relatedArticles($product, $this->findRelatedArticles($product)),
            default => null,
        };
    }

    private function namedProduct(string $normalized): ?Product
    {
        $tokens = $this->normalizer->tokens($normalized);
        $products = Product::query()->published()->with(['tags', 'categories'])
            ->where(function ($query) use ($tokens) {
                foreach (array_slice($tokens, 0, 30) as $token) {
                    if (mb_strlen($token) >= 3) {
                        $query->orWhere('name', 'like', '%'.addcslashes($token, '%_\\').'%');
                    }
                }
            })->orderByDesc(DB::raw('LENGTH(name)'))->limit(80)->get();
        foreach ($products as $product) {
            if ($this->normalizer->containsPhrase($normalized, $product->name)
                && $this->catalog->freshProduct((int) $product->id)) {
                return $product;
            }
        }

        return null;
    }

    private function isContextualQuestion(string $normalized): bool
    {
        $remainder = preg_replace('/\b(what|is|its|it|the|a|an|this|that|please|show|me|tell|about|can|you|i|do|how|to|use|of|price|cost|much|in|out|stock|available|availability|ingredients?|supplement|facts|formula|directions|dosage|label|serving|related|articles?|blog|read|more|kitne|ka|hai)\b/u', '', $normalized);
        $remainder = str_replace(['قیمت', 'کیا', 'ہے'], '', $remainder ?? $normalized);

        return trim(preg_replace('/[\p{P}\s]+/u', '', $remainder) ?? $remainder) === '';
    }

    private function detectFollowUp(string $normalized): ?string
    {
        $map = [
            'price' => ['price', 'cost', 'kitne ka', 'how much', 'قیمت'],
            'stock' => ['in stock', 'available', 'availability', 'stock', 'out of stock'],
            'ingredients' => ['ingredient', 'ingredients', 'what is in', 'formula', 'supplement facts'],
            'directions' => ['how do i use', 'how to use', 'directions', 'dosage label', 'serving'],
            'articles' => ['related article', 'related articles', 'show articles', 'blog', 'read more'],
        ];

        foreach ($map as $key => $phrases) {
            foreach ($phrases as $phrase) {
                if ($this->normalizer->containsPhrase($normalized, $phrase)) {
                    return $key;
                }
            }
        }

        // Intent router follow-ups when session already has a product.
        if ($this->state->selectedProductId() || $this->state->offeredProductIds() !== []) {
            $intent = $this->intents->route($normalized);

            return match ($intent) {
                AssistantIntent::PriceOrAvailability => str_contains($normalized, 'stock') || str_contains($normalized, 'available')
                    ? 'stock'
                    : 'price',
                AssistantIntent::IngredientsOrDirections => str_contains($normalized, 'direction') || str_contains($normalized, 'use')
                    ? 'directions'
                    : 'ingredients',
                AssistantIntent::RelatedArticles => 'articles',
                default => null,
            };
        }

        return null;
    }

    /**
     * @return list<AssistantContentCard>
     */
    private function findRelatedArticles(Product $product): array
    {
        $tokens = $this->normalizer->tokens($product->name.' '.($product->categories->first()?->name ?? ''));
        $query = Post::query()->published()->limit(8);

        if ($tokens !== []) {
            $query->where(function ($q) use ($tokens) {
                foreach (array_slice($tokens, 0, 4) as $token) {
                    if (mb_strlen($token) < 3) {
                        continue;
                    }
                    $like = '%'.addcslashes($token, '%_\\').'%';
                    $q->orWhere('title', 'like', $like)
                        ->orWhere('excerpt', 'like', $like);
                }
            });
        }

        $cards = [];
        foreach ($query->latest('published_at')->get() as $post) {
            $card = $this->catalog->postCard((int) $post->id);
            if ($card) {
                $cards[] = $card;
            }
            if (count($cards) >= (int) config('assistant.limits.max_articles_per_answer', 2)) {
                break;
            }
        }

        if ($cards === []) {
            $guide = Post::query()->published()->where('slug', 'how-to-read-a-supplement-label')->first();
            $card = $guide ? $this->catalog->postCard((int) $guide->id) : null;
            if ($card) {
                $cards[] = $card;
            }
        }

        return $cards;
    }

    private function rememberFromResponse(AssistantResponse $response, string $query, AssistantIntent $intent): void
    {
        $productIds = array_map(fn ($p) => $p->id, $response->products);
        $sourceIds = array_map(
            fn ($s) => $s->type->value.':'.$s->id,
            $response->sources,
        );

        if ($productIds !== [] || $response->type === AssistantResponseType::Unknown) {
            $this->state->setTopic(null);
        }
        $this->state->setIntent($response->intent);
        $this->state->setOffered($productIds, $sourceIds, $query);

        if (count($productIds) === 1) {
            $this->state->setSelectedProductId($productIds[0]);
        } else {
            $this->state->setSelectedProductId(null);
        }
    }

    /**
     * @return array{response: AssistantResponse, state: array<string, mixed>}
     */
    private function finish(AssistantResponse $response): array
    {
        $history = session('assistant.transcript', []);
        $history[] = ['id' => 'assistant-'.Str::uuid(), 'role' => 'assistant', 'text' => $response->answer, 'isError' => false, 'response' => $response->toArray()];
        session()->put('assistant.transcript', array_slice($history, -40));

        return [
            'response' => $response,
            'state' => $this->state->get(),
        ];
    }
}
