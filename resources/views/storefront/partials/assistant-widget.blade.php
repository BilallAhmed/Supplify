@php
    /** @var \App\Services\Assistant\AssistantSettings $assistantSettings */
    $assistantName = $assistantSettings->publicName();
    $assistantTagline = $assistantSettings->tagline();
    $maxLen = $assistantSettings->limit('message_max_length', 500);
    $welcomeChoices = [
        ['label' => 'Find a product', 'message' => 'Help me find a product'],
        ['label' => 'Check price or stock', 'message' => 'I want to check a product price or availability'],
        ['label' => 'Read ingredients', 'message' => 'Show ingredients or supplement facts'],
        ['label' => 'Delivery and returns', 'message' => 'Tell me about delivery and returns'],
        ['label' => 'Contact Us', 'href' => route('pages.show', 'contact')],
    ];
@endphp

<div
    class="assistant-widget"
    data-assistant-widget
    data-lenis-prevent
    x-data="supplifyAssistant({
        name: @js($assistantName),
        tagline: @js($assistantTagline),
        messageUrl: @js(route('assistant.message')),
        resetUrl: @js(route('assistant.reset')),
        historyUrl: @js(route('assistant.history')),
        contactUrl: @js(route('pages.show', 'contact')),
        maxLength: {{ (int) $maxLen }},
        welcomeChoices: @js($welcomeChoices),
    })"
    @keydown.window="onKeydown($event)"
>
    <div class="assistant-widget__live" aria-live="polite" x-text="liveMessage"></div>

    <div
        id="assistant-widget-panel"
        class="assistant-widget__panel"
        x-ref="panel"
        x-show="open"
        x-cloak
        role="dialog"
        aria-modal="false"
        :aria-hidden="(!open).toString()"
        aria-labelledby="assistant-widget-title"
        data-assistant-panel
        @keydown.escape.stop="close()"
    >
        <header class="assistant-widget__header">
            <div class="assistant-widget__heading">
                <h2 id="assistant-widget-title" class="assistant-widget__title">{{ $assistantName }}</h2>
                <p class="assistant-widget__tagline">{{ $assistantTagline }}</p>
            </div>
            <div class="assistant-widget__header-actions">
                <button
                    type="button"
                    class="assistant-widget__icon-btn"
                    @click="toggleSound()"
                    :aria-pressed="soundEnabled.toString()"
                    :aria-label="soundEnabled ? 'Mute reply sound' : 'Enable reply sound'"
                    :data-tooltip="soundEnabled ? 'Mute reply sound' : 'Enable reply sound'"
                    aria-label="Mute reply sound"
                >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M11 5 6 9H3v6h3l5 4V5Z" stroke-linecap="round" stroke-linejoin="round"/>
                        <path x-show="soundEnabled" d="M15 8a6 6 0 0 1 0 8m3-11a10 10 0 0 1 0 14" stroke-linecap="round"/>
                        <path x-show="!soundEnabled" d="m16 9 6 6m0-6-6 6" stroke-linecap="round"/>
                    </svg>
                </button>
                <button
                    type="button"
                    class="assistant-widget__icon-btn"
                    @click="resetChat()"
                    :disabled="sending || restoring"
                    aria-label="Reset chat"
                    data-tooltip="Reset chat"
                >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 10a9 9 0 1 1 2.6 8.4M3 4v6h6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <button
                    type="button"
                    class="assistant-widget__icon-btn"
                    x-ref="closeBtn"
                    @click="close()"
                    aria-label="Close {{ $assistantName }}"
                    data-tooltip="Close"
                >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" stroke-linecap="round"/></svg>
                </button>
            </div>
        </header>

        <div class="assistant-widget__body">
            <div class="assistant-widget__transcript" x-ref="transcript" tabindex="-1">
                <div class="assistant-widget__empty" x-show="messages.length === 0 && !loading">
                    <p class="assistant-widget__empty-title">How can we help?</p>
                    <p class="assistant-widget__empty-copy">
                        I search Supplify’s published website information—products, articles, and policies. I do not provide medical advice.
                    </p>
                    <div class="assistant-widget__welcome" role="group" aria-label="Suggested starting questions">
                        <template x-for="choice in welcomeChoices" :key="choice.label">
                            <button
                                type="button"
                                class="assistant-widget__welcome-btn"
                                @click="welcome(choice)"
                                :disabled="sending || restoring"
                                x-text="choice.label"
                            ></button>
                        </template>
                    </div>
                </div>

                <template x-for="item in messages" :key="item.id">
                    <div
                        class="assistant-widget__bubble"
                        :class="{
                            'assistant-widget__bubble--user': item.role === 'user',
                            'assistant-widget__bubble--assistant': item.role === 'assistant',
                            'assistant-widget__bubble--error': item.isError
                        }"
                    >
                        <p x-text="item.text"></p>

                        <template x-if="item.response?.products?.length">
                            <div class="assistant-widget__cards">
                                <template x-for="product in item.response.products" :key="'p-'+product.id">
                                    <a class="assistant-widget__card" :href="product.url">
                                        <img
                                            class="assistant-widget__card-media"
                                            :src="product.image_url || '{{ asset('images/blog/placeholder.svg') }}'"
                                            alt=""
                                            width="52"
                                            height="52"
                                            loading="lazy"
                                        >
                                        <div>
                                            <p class="assistant-widget__card-title" x-text="product.name"></p>
                                            <p class="assistant-widget__card-meta">
                                                <span x-text="product.price_formatted"></span>
                                                <span aria-hidden="true"> · </span>
                                                <span x-text="product.stock_label"></span>
                                            </p>
                                        </div>
                                    </a>
                                </template>
                            </div>
                        </template>

                        <template x-if="item.response?.contents?.length">
                            <div class="assistant-widget__cards">
                                <template x-for="content in item.response.contents" :key="'c-'+content.type+'-'+content.id">
                                    <a class="assistant-widget__card" :href="content.url">
                                        <img
                                            class="assistant-widget__card-media"
                                            :src="content.image_url || '{{ asset('images/blog/placeholder.svg') }}'"
                                            alt=""
                                            width="52"
                                            height="52"
                                            loading="lazy"
                                        >
                                        <div>
                                            <p class="assistant-widget__card-title" x-text="content.title"></p>
                                            <p class="assistant-widget__card-meta" x-text="content.meta || content.type"></p>
                                        </div>
                                    </a>
                                </template>
                            </div>
                        </template>

                        <template x-if="item.response?.clarifications?.length">
                            <div class="assistant-widget__choices" role="group" aria-label="Clarification choices">
                                <template x-for="choice in item.response.clarifications" :key="choice.id">
                                    <button
                                        type="button"
                                        class="assistant-widget__choice"
                                        @click="sendChoice(choice)"
                                        :disabled="sending || restoring"
                                        x-text="choice.label"
                                    ></button>
                                </template>
                            </div>
                        </template>

                        <template x-if="item.response?.follow_ups?.length">
                            <div class="assistant-widget__choices" role="group" aria-label="Follow-up questions">
                                <template x-for="choice in item.response.follow_ups" :key="choice.id">
                                    <button
                                        type="button"
                                        class="assistant-widget__choice"
                                        @click="sendChoice(choice)"
                                        :disabled="sending || restoring"
                                        x-text="choice.label"
                                    ></button>
                                </template>
                            </div>
                        </template>

                        <template x-if="item.isError">
                            <div class="assistant-widget__choices">
                                <button type="button" class="assistant-widget__choice" @click="retryLast()" :disabled="sending || restoring">Retry</button>
                            </div>
                        </template>
                    </div>
                </template>

                <p class="assistant-widget__status" x-show="loading" x-cloak>Finding that for you…</p>
            </div>

            <div class="assistant-widget__footer">
                <form class="assistant-widget__composer" @submit.prevent="sendMessage()">
                    <label class="sr-only" for="assistant-widget-input">Message {{ $assistantName }}</label>
                    <textarea
                        id="assistant-widget-input"
                        class="assistant-widget__input"
                        x-ref="composer"
                        x-model="draft"
                        rows="1"
                        maxlength="{{ (int) $maxLen }}"
                        placeholder="Ask about products or store help…"
                        :disabled="sending || restoring"
                        @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); sendMessage(); }"
                    ></textarea>
                    <button type="submit" class="assistant-widget__send" aria-label="Send message" :disabled="sending || restoring || draft.trim().length < 2">
                        <span x-text="loading ? 'Sending…' : 'Send'">Send</span>
                        <svg x-show="!loading" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m5 12 14-7-5 14-3-6-6-1Z" stroke-linecap="round" stroke-linejoin="round"/><path d="m11 13 8-8" stroke-linecap="round"/></svg>
                    </button>
                </form>
                <div class="assistant-widget__meta-row">
                    <p class="assistant-widget__hint">Website info · not medical advice</p>
                    <a class="assistant-widget__link" href="{{ route('pages.show', 'contact') }}">Contact Us</a>
                </div>
                <p class="assistant-widget__status assistant-widget__status--error" x-show="error" x-text="error" x-cloak></p>
            </div>
        </div>
    </div>

    <button
        type="button"
        class="assistant-widget__launcher"
        x-ref="launcher"
        @click="toggle()"
        :aria-expanded="open.toString()"
        aria-haspopup="dialog"
        aria-controls="assistant-widget-panel"
        aria-label="Open Supplify Assistant"
        :aria-label="open ? 'Close Supplify Assistant' : 'Open Supplify Assistant'"
        data-tooltip="Supplify Assistant"
        data-assistant-launcher
    >
        <span class="assistant-widget__launcher-icon" aria-hidden="true">
            <svg class="assistant-widget__mascot" width="44" height="44" viewBox="0 0 48 48" fill="none" focusable="false">
                <path d="M24 15v-5" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
                <g class="assistant-widget__mascot-leaf">
                    <path d="M24 11C23 5 29 2 36 4c0 7-6 11-12 7Z" fill="var(--color-accent, #00b883)"/>
                    <path d="m25 10 6-3" stroke="#d9f6ec" stroke-width="1.5" stroke-linecap="round"/>
                </g>
                <rect x="3" y="23" width="5" height="10" rx="2.5" fill="var(--color-accent, #00b883)"/>
                <rect x="40" y="23" width="5" height="10" rx="2.5" fill="var(--color-accent, #00b883)"/>
                <path d="M18 15h12c7 0 11 4 11 11v3c0 7-4 11-11 11H20l-8 4v-6c-4-2-5-5-5-9v-3c0-7 4-11 11-11Z" fill="#fff"/>
                <rect x="12" y="20" width="24" height="14" rx="7" fill="var(--color-brand, #054648)"/>
                <g class="assistant-widget__mascot-eyes" fill="#9ce9cc">
                    <rect x="17" y="24" width="4" height="5" rx="2"/>
                    <rect x="27" y="24" width="4" height="5" rx="2"/>
                </g>
                <path d="M21 36c2 1.4 4 1.4 6 0" stroke="var(--color-brand, #054648)" stroke-width="1.7" stroke-linecap="round"/>
            </svg>
        </span>
        <span class="assistant-widget__live">{{ $assistantName }}</span>
    </button>
</div>
