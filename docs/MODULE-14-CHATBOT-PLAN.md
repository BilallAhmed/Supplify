# Module 14 — Supplify shopping assistant implementation plan

Status: user-selected website-only assistant; no language model or external AI API required. See the confirmed direction section below, which supersedes the earlier provider proposal.
Reviewed: 23 October 2026. Pakistan / PKR storefront. Payment gateway remains deferred.

### Implementation progress

| Layer | Scope | Status |
|-------|--------|--------|
| 1 | Content contract, eligibility, min schema/config, admin auth; widget off | **Complete** (23 Oct 2026) |
| 2 | Automatic knowledge index + sync | **Complete** (23 Oct 2026) |
| 3 | Search and matching | **Complete** (23 Oct 2026) |
| 4 | Conversation and answer construction | **Complete** (23 Oct 2026) |
| 5 | Customer chat widget | **Complete** (23 Oct 2026) |
| 6 | Admin FAQ/alias/ops UI | **Complete** (23 Oct 2026) |
| 7 | Final acceptance | **Complete** (23 Oct 2026) |

#### Layer 1 deliverables

- **Intents:** `AssistantIntent` — find product; price/availability; ingredients/directions; related articles; delivery/returns/contact; clarify; unknown; boundary.
- **Response contract:** `AssistantResponse` (+ source refs, product cards, content cards, follow-up/clarification choices) — structured arrays only, no generated HTML.
- **Eligibility:** `AssistantEligibility` — published/current + not deleted/disabled/future + explicit `assistant_source_approvals` (FAQs: active flag). `editorial-draft` tag blocks products even if approved. Public visibility alone is insufficient.
- **Fields:** documented in `config/assistant.php` (`readable_fields` / `prohibited_fields`).
- **Schema:** `assistant_faqs`, `assistant_aliases`, `assistant_source_approvals`.
- **Config:** `ASSISTANT_ENABLED` (default false) + runtime `store_settings.assistant.enabled`; both required for `AssistantSettings::isEnabled()`.
- **Admin:** `/admin/assistant` settings + approve/withdraw endpoints; policies deny customers.
- **Not in Layer 1:** index sync, retrieval, chat API, storefront widget, full FAQ CRUD UI.

#### Layer 2 deliverables

- **Index schema:** `assistant_documents`, `assistant_document_chunks`, `assistant_sync_states`.
- **Indexer:** `AssistantKnowledgeIndexer` builds revision hashes, bounded chunks, removes ineligible sources; `rebuildAll` + `reconcile`.
- **Sync:** `AssistantKnowledgeSync` dispatches `RefreshAssistantDocumentJob` after DB commit (with revision + timestamp for out-of-order protection).
- **Hooks:** Model observers (product, variation, category, post, page, tag, FAQ, alias, source approval) plus explicit CatalogService / BlogService / ProductVariationService pivot & bulk hooks.
- **Live gates:** `AssistantKnowledgeReader::usableDocument` re-checks eligibility; omits stale excerpts; `liveProductFallback` for newly eligible products while indexing catches up.
- **Ops:** `php artisan assistant:rebuild-knowledge [--sync]`; hourly `ReconcileAssistantKnowledgeJob` via scheduler; sync counters on `/admin/assistant`; failed jobs record `last_failure` sync state.
- **Not in Layer 2:** keyword ranking/aliases search (Layer 3), conversation answers (Layer 4), storefront widget (Layer 5), full rebuild UI (Layer 6).

#### Layer 3 deliverables

- **Engine interface:** `AssistantSearchEngine` with `PortableTokenSearchEngine` (default/SQLite) and `MysqlFullTextSearchEngine` (optional indexes + portable fallback). Config: `ASSISTANT_SEARCH_DRIVER=auto|portable|fulltext`. No vector DB.
- **Orchestration:** `AssistantKnowledgeSearch` + `AssistantIntentRouter` + `AssistantAliasResolver`; outcomes via `AssistantSearchOutcome` / `AssistantSearchHit`.
- **Ranking:** exact name → ingredient phrase (whole-token) → aliases → title tokens → category/goal → body; title weighted above body.
- **Vitamin D3 safeguard:** phrase/token matching; bare `vitamin` does not dump all vitamin products; D3 does not match Vitamin E.
- **Ambiguity:** close scores → clarification choices; low/none → honest unknown; boundary phrases → no product push.
- **Aliases:** reviewed `assistant_aliases` rows; starter seeder `AssistantAliasSeeder` (en / roman_ur / ur) — not full multilingual coverage.
- **Goals:** explicit `config('assistant.search.wellness_goals')` → approved categories only (no medical inference).
- **Not in Layer 3:** session follow-ups / live price cards (Layer 4), widget (Layer 5), FAQ/alias admin CRUD (Layer 6).

#### Layer 4 deliverables

- **Session state:** `AssistantConversationState` — intent, selected product, offered source/product IDs, turn count; idle expiry + reset.
- **Safety:** `AssistantSafetyRules` — sanitize input/excerpts, instruction-injection refusal, medical boundary copy (no product push).
- **Live cards:** `AssistantCatalogResolver` re-reads price/currency/stock/purchasability and route URLs; skips ineligible records.
- **Templates:** `AssistantResponseBuilder` + `AssistantConversationService` for search answers and follow-ups (price, stock, ingredients, directions-as-label, related articles, which-product clarify).
- **HTTP JSON API (widget later):** `POST /assistant/message`, `POST /assistant/reset` — CSRF + throttle; **503 unless** config + runtime enable. No UI widget yet (Layer 5).
- **Not in Layer 4:** storefront launcher/panel UI, admin FAQ CRUD, production enablement.

#### Layer 5 deliverables

- **Gated mount:** storefront layout includes the widget only when `AssistantSettings::isEnabled()` (config `ASSISTANT_ENABLED` + runtime `store_settings.assistant.enabled`).
- **Chrome:** left-fixed launcher + panel (avoids back-to-top on the right); theme tokens; welcome choices; transcript with product/content cards, clarifications, follow-ups; composer; reset/close; Contact Us link.
- **Client:** Alpine `supplifyAssistant` posts to message/reset with CSRF via axios; loading/error/retry; Escape closes; polite live region; `prefers-reduced-motion` respected.
- **Assets:** `resources/js/assistant-widget.js`, `resources/css/assistant-widget.css` imported from `app.js`.
- **Not in Layer 5:** FAQ/alias admin CRUD or rebuild UI (Layer 6); production enablement / final acceptance (Layer 7).

#### Layer 6 deliverables

- **FAQ CRUD:** `/admin/assistant/faqs` — create/edit/delete with intent, aliases text, sort order, active flag; authorized via `AssistantFaqPolicy`.
- **Alias CRUD:** `/admin/assistant/aliases` — phrase/locale/intent/optional target; `normalized_phrase` set via `AssistantTextNormalizer`; authorized via `AssistantAliasPolicy`.
- **Ops:** settings hub links + counts; **Queue knowledge rebuild** and **Rebuild now (sync)** via `POST /admin/assistant/rebuild`.
- **Not in Layer 6:** turning production on permanently (ops decision after Layer 7 checklist).

#### Layer 7 deliverables

- **Acceptance suite:** `AssistantAcceptanceTest` covers eligible links, draft exclusion, live price/stock, Vitamin D3 honesty, medical boundary, deleted-source exclusion, dual-gate widget/API, guided follow-ups, no AI provider config.
- **Ops note:** public enablement still requires `ASSISTANT_ENABLED=true` **and** admin runtime enable; queue worker + scheduler required for sync. Module marked complete as website-only guided assistant.

## Existing scope and starting point

MODULES.md, MODULE-TRACKER.md, ARCHITECTURE.md and QUOTATION-SPEC.md specify a sticky shopping assistant with grounded answers, real product URLs, session context, fallbacks and medical boundaries. Provider/model is unconfirmed. No chatbot implementation was found in the inspected application files.

Reuse Laravel, Blade, Alpine, GlobalSearchService, published product/post/page scopes, categories, variations, StoreSettings and existing media. The catalog currently contains 15 products. Do not introduce a vector database or model fine-tuning for this initial catalog.

## Recommended architecture

Browser widget -> throttled web-session/CSRF chat endpoint -> safety and intent routing -> approved content retrieval -> live catalog lookup -> optional provider -> output validation -> answer with product/article/page cards.

Provide two honest operating modes:

1. Guided mode: no language model, no external API fees. Keyword/synonym and category matching, structured follow-up buttons, approved FAQ answers, product/article links. It is a guided store helper, not equivalent to unrestricted AI conversation.
2. AI mode: the same retrieval and response cards, with a replaceable provider for natural language, follow-up understanding and short grounded explanations. On quota limits, timeout or disabled provider, return the guided fallback rather than losing the conversation.

Website data does not train or update a model automatically. Retrieve current facts for each question. Product price, currency, purchasability and stock must be checked live immediately before rendering cards. Cache stable article snippets with invalidation on publish/edit/delete; never cache customer/order information into public knowledge.

## Customer experience

- Theme-matched launcher, accessible panel, loading/error/empty states, close and reset controls.
- Start choices: Find a product, Understand an ingredient, Read wellness articles, Delivery and returns, Contact support.
- Understand product names, ingredient names and approved general wellness goals. Ask a short clarifying question only when it improves the match; avoid collecting medical histories.
- At most 2–3 relevant product cards and 1–2 articles per answer. Explain the match using approved content, show PKR prices and availability, link to product details or quick view.
- General navigation and policy questions link to the relevant published page. Unknown store policies are not guessed.
- Keep related recommendations optional; do not quietly add items to cart, change addresses, subscribe users or place orders.
- Support English first; include Roman Urdu and Urdu in language evaluation before promising multilingual quality.
- Vitamin D3 example: no exact product is in the current catalog. Say so, offer an article only if a relevant approved article exists, and provide Contact Us. Do not substitute unrelated vitamins.

## Content readiness and approval

Public availability is not enough to imply medical/editorial approval. Current draft descriptions, placeholder policies, demo prices and example contact details must be identified before launch.

Create an admin assistant area for approved FAQ entries, approved content sources, product discovery tags/synonyms, enabled state and fallback wording. Product health claims require reviewed evidence in the supplied content. Omit editorial-draft content from recommendation explanations until approved. Archived, deleted, unpublished and future posts must be excluded. Never infer ingredients from product photographs.

Use source IDs, content revision and approval state for retrieved snippets. Generate links and cards server-side from verified IDs. Mark test/demo data clearly in previews; replace before public launch.

## Medical, privacy and security boundaries

- This is shopping and product-information assistance, not diagnosis, treatment, prescriptions or personalized dosing. Quote label directions only as published and approved; do not decide suitability from symptoms, medications or health conditions.
- Pregnancy, children, medication interactions and condition-specific suitability should route to a qualified professional and/or support, not trigger a product sales recommendation.
- Keep keys server-side; no arbitrary SQL, file access, browsing or unrestricted tools for the model.
- Treat retrieved content and user messages as data, not instructions. Reject unknown product/source IDs and external invented URLs. Escape/sanitize rendered output. Grounding reduces hallucinations but does not guarantee their absence; evaluate unsupported claims explicitly.
- Do not send account records, orders, addresses or medical histories to a provider. Explain external processing before enabling AI; review provider terms and minimize/redact personal details.
- Store only bounded, short-lived conversation context. Default to aggregate analytics without raw chat transcripts; define retention/consent before optional transcript storage. Provide reset/delete behavior.
- Rate limits, message-length limits, maximum turns/context size, provider timeout, bounded retries, concurrency controls and token/cost caps. No credentials in logs. Return deterministic fallback on errors.

## Provider options researched

- GroqCloud: free plan has model-specific request/token quotas. Suitable to evaluate for a development proof of concept, not an unlimited production guarantee. Review current model availability, account access, privacy settings and retention before connecting.
- Gemini Developer API: free tier for selected models, with limits. Unpaid-service terms allow data use for product improvement and warn against submitting sensitive/confidential/personal information. Not the default for real customer health-related conversations under unpaid terms.
- Ollama local models: local inference can avoid external per-request charges and keep processing on controlled hardware. Hosting, RAM/GPU capacity, uptime, model licensing and operational support still cost money. A developer laptop is not a production service.
- Guided mode: no model/API cost; reliable for a constrained FAQ/product finder, but much less flexible for long, ambiguous or multilingual conversations.

Recommendation: implement the provider-independent guided/retrieval foundation first, then evaluate a Groq free-tier prototype with synthetic questions and public approved catalog content. Keep production provider selection open until conversation quality, data terms, quotas and hosting are checked. Do not upload customer chats or connect any provider during planning.

Sources reviewed:
- https://console.groq.com/docs/rate-limits
- https://console.groq.com/docs/your-data
- https://ai.google.dev/gemini-api/docs/pricing
- https://ai.google.dev/gemini-api/terms
- https://docs.ollama.com/faq

## Delivery sequence

1. Knowledge audit: identify approved products/articles/FAQs/policies and missing descriptions; agree supported intents and medical boundaries.
2. Foundation: AssistantKnowledgeService, live catalog resolver, intent/synonym matching, versioned approval settings, guided answer templates and tests. Build widget against this backend.
3. Provider adapter: AiProvider interface with configurable provider/model and disabled-by-default external integration. Use structured response fields for answer/source/product/article IDs, validated server-side.
4. Conversation: short-lived session context, relevant follow-ups, source links, product/article cards, fallback and contact handoff. No private order tracking in the first release.
5. Evaluation: realistic synthetic questions, English/Roman Urdu/Urdu checks, missing products, stale prices/stock, drafts, prompt injection, medical requests, invalid provider output and quota outages.
6. Client preview and content sign-off. Enable production AI only after provider credentials, data handling, usage limits and approved knowledge are confirmed.

## Acceptance criteria

- Listed product/article/page links always resolve to eligible records; no fabricated items or URLs.
- A price/stock edit is reflected in the next relevant answer/card; draft/unpublished items never leak.
- Product guidance explains only verified, approved attributes; unavailable Vitamin D3 is handled honestly.
- Cross-turn clarification works in AI mode; guided mode clearly offers structured options.
- Medical/sensitive requests receive the appropriate boundary response without sales pressure.
- Provider outages fall back gracefully; abuse limits and spend controls work.
- Widget keyboard access, focus handling, loading/error states and page overlap are checked.
- Customer messages are not used as public knowledge; retention and external processing are documented.

## Confirmed direction: website-only assistant, no AI API

User decision: use the website's own information, automatically keep knowledge current as the catalog and articles grow, and avoid paid AI APIs. The first release must not require an external AI provider, API key, local language model, embeddings API or model training. This decision supersedes the earlier recommendation to prototype a cloud AI provider. Provider comparisons above are background research only, not the implementation direction.

### What the assistant is

A conversational search and guided product-discovery system. It can answer supported store questions, find exact products and ingredients, retrieve relevant approved articles and pages, and ask structured clarifying questions. It is not a generative language model and must not promise unrestricted understanding, reasoning or medical suitability decisions.

### Automatic content lifecycle

1. Laravel observers listen for product, variation, category, tag/pivot, post, page and approved FAQ changes. Catalog and content services explicitly invalidate related records when pivot changes do not emit model events. Dispatch index work after the database transaction commits.
2. Create/update searchable chunks from approved public fields. Preserve source type, source ID, source URL generated by the application, title, content revision/hash, indexed timestamp, approval state and publication schedule. Do not infer facts or health benefits.
3. Drafts, editorial-draft material, disabled/deleted products, unpublished/future posts and pages are ineligible. Unpublish/delete/approval withdrawal invalidates content immediately. Every answer also checks source eligibility live so a queued deletion cannot leak stale text.
4. At response time, hydrate product cards from live records: name, current PKR price, variation eligibility, stock and real links. Hydrate article links/titles from current published records. Indexed text is a discovery aid, not the authority for changing facts.
5. A scheduled incremental reconciliation catches missed events, scheduled publication and stale/missing records. A manual rebuild command and admin Rebuild Knowledge button provide recovery and initial imports. Queues/scheduler must run in production; expose pending/failed updates and last successful sync.
6. Show all eligible new products immediately through a live catalog search path while indexing catches up. For content changes, use a source revision check and live-field extraction when an index entry is stale; otherwise skip the stale entry. Never imply background indexing is instantaneous.
7. New facts are automatically searchable without copying data into a prompt or retraining. A new synonym, new question type or completely new recommendation rule still requires a maintained rule/alias entry; content indexing alone does not teach arbitrary new language or reasoning.

### Retrieval and conversation

- Build a database-backed assistant_documents/chunks index with source IDs, normalized text, aliases, categories and explicit approved discovery metadata. Use portable token matching initially and database full-text indexes when supported; isolate search behind an interface for future larger-scale engines.
- Rank exact product/ingredient names first, then reviewed aliases and category/goal matches, followed by relevant document text. Weight titles above body text. Avoid unrestricted substring matches that confuse Vitamin D with other vitamins.
- Support common English/Roman Urdu/Urdu phrases through an explicit reviewed alias dictionary and quick-reply choices. Do not promise full multilingual conversation without evaluating it.
- Track bounded short-lived session state: current intent, selected product/category and last offered source IDs. Understand supported follow-ups such as price, stock, ingredients and related articles using that state. Never persist an unbounded transcript by default.
- Compose answers from approved templates and verbatim approved excerpts. Link to sources. For ambiguous matches, show options or ask one focused question. With insufficient evidence, say so and offer website search/contact.
- Product recommendations use explicit reviewed goal/ingredient mappings, never invented suitability from symptom descriptions. Prefer an exact match; clearly label broader alternatives only when the user requests them and evidence supports the relationship.

### Suggested internal modules

AssistantKnowledgeIndexer; AssistantKnowledgeSearch; AssistantIntentRouter; AssistantConversationState; AssistantCatalogResolver; AssistantResponseBuilder; AssistantSafetyRules. The public endpoint remains in the Laravel web/session stack with CSRF, rate limits and bounded input. No external browsing or private account/order lookup in the initial release.

### First implementation sequence

1. Define eligible sources, approval rules, discovery metadata and supported intents.
2. Add index schema, initial import, after-commit observers/service hooks, reconciliation schedule and sync status.
3. Implement keyword/full-text retrieval, aliases and exact-match safeguards.
4. Implement deterministic answers, live product cards, article/page links and structured follow-ups.
5. Build the theme-matched widget and admin FAQ/alias/knowledge controls.
6. Test new product creation, article edits, future publication, deletion/unpublish, category reassignment, stale indexes, queue outages, price/stock changes, unknown products, ambiguous questions, prompt-injection text and unsupported medical requests.

### Acceptance additions

- New eligible products are discoverable without a deployment or manual knowledge upload.
- Product price/stock changes appear in the next response; source text edits do not keep serving old excerpts.
- Deleted/unpublished/unapproved sources are excluded even when an indexing job is pending.
- Scheduled publication appears after becoming eligible; reconciliation covers missed jobs.
- UI/source counts grow with the catalog; ranking remains paginated/bounded and does not load the full catalog for each message.
- The assistant remains operational without any AI credentials or third-party AI network call. Normal application hosting, storage and queue maintenance still apply.

