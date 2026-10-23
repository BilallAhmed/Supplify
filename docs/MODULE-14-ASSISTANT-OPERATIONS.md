# Supplify Assistant — operation and acceptance

Updated 23 October 2026. This supersedes the initial QA findings for the fixes listed below.

## Current local setup

- Website-only, deterministic assistant. No AI API, model key, embeddings service or external chat processing.
- 14 reviewed public products, 7 pages, 4 categories and 2 general education articles approved; 3 active FAQs; 22 reviewed starter/product aliases. Initial index: 30 active documents / 121 chunks.
- Magnesium 250mg remains blocked because its source is tagged editorial-draft. The existing draft tag/content was preserved. Missing ingredient/facts/directions fields on other products remain explicitly unavailable; the assistant does not invent them.
- Removed obsolete US shipping copy and placeholder contact email from published page bodies; Pakistan delivery guidance refers to checkout options/contact without making up delivery dates. Existing Returns policy retained.
- Runtime and config gates enabled. Local assistant queue worker and scheduler started as hidden processes. These processes are local development services, not a deployment supervisor or an automatic Windows restart configuration.

## Admin workflow

Open `/admin/assistant` while signed in as an admin.

1. Choose **Review sources**, then Products, Articles, Pages or Categories.
2. Search and preview the source content. Review the actual product/article/page before approving.
3. Choose **Approve source** to allow approved published content into knowledge; **Withdraw approval** removes eligibility immediately even if indexing is delayed. Draft/unpublished/inactive items remain blocked.
4. Edit reviewed FAQs and aliases using their existing controls. An active FAQ is its approval state.
5. Use **Queue knowledge rebuild** for routine recovery/import. The inline rebuild is available for this small catalog; prefer the queue as content grows.
6. Check active document/chunk counts, last successful sync, last reconciliation, pending/failed assistant jobs and oldest pending age. An empty index displays an explicit setup warning.

New product/content facts update automatically after edits to an approved source. A new source still requires explicit initial review/approval. Publication scheduling, after-commit jobs, revisions, reconciliation and live eligibility prevent drafts/stale content from becoming authoritative. New language patterns still need maintained aliases/rules.

## Queue and scheduler

Assistant jobs use a dedicated `assistant` queue. Do not run only the default queue and assume knowledge will update.

Worker (production: supervise and restart on deployment):

```sh
php artisan queue:work --queue=assistant --sleep=2 --tries=3 --timeout=120
```

Scheduler (local development):

```sh
php artisan schedule:work
```

Production scheduler: run `php artisan schedule:run` once per minute using the host scheduler. The assistant reconciliation itself is hourly. Configure one scheduler instance as appropriate to the deployment. Run the project's other application queues separately.

Recovery:

```sh
php artisan assistant:rebuild-knowledge
php artisan assistant:rebuild-knowledge --sync
```

Before retrying failed jobs, inspect their error and verify source eligibility. Do not approve drafts or disable eligibility to clear failures. Local logs: `storage/logs/assistant-worker.log`, `assistant-worker-error.log`, `assistant-scheduler.log`, `assistant-scheduler-error.log`. Deploy the application/database approvals, FAQ and alias data together; Git does not copy the live knowledge setup database.

## Corrected behavior

- Medical boundary intent runs before aliases and contextual answers, including after product selection.
- Explicitly naming a different product overrides old product context; unknown Vitamin D3 never borrows Vitamin E's price.
- Price, stock, ingredients, directions and article quick replies execute their actual actions and validate offered/selected IDs.
- Retry preserves the original action/payload; duplicate submits are blocked while a request is pending.
- Bounded private session history restores on navigation and clears with reset/idle expiry. History responses are private/no-store.
- Shift+Enter creates a new line; plain Enter submits. Composer enlarged and product thumbnails use contain.
- Article results are scoped to articles; delivery/returns requests retrieve matching policy pages and readable excerpts.
- Portable exact-title candidates are independent of broad result limits; MySQL FULLTEXT candidates are actually used and scored through the same precision rules. Rebuild processes sources in bounded ID batches.
- Theme icon launcher remains accessible with Open/Close labels, visible focus and reduced-motion handling.

## Verification

- PHP assistant suite: 81 passed, 280 assertions, including 9 added regressions for actual action payloads, medical precedence, missing/new product context, source-review authorization and history/reset.
- JavaScript behavioral tests: 3 passed (retry payload, history/reset, duplicate submission). Run from the project root: `node --test tests/js/assistant-widget.test.mjs`.
- Production frontend build passed. Existing Sofia font build-time resolution warning is unrelated; the browser resolves static font assets separately.
- Desktop Chrome checked real CoQ10 lookup, Price quick reply (PKR 2,800), switch to Vitamin E price (PKR 2,150), contextual pregnancy boundary, article quick reply and real article navigation, restored transcript, multiline Shift+Enter, Pakistan delivery/returns, and admin withdrawal/reapproval. No orders/emails/contact enquiries were submitted.
- Local queue processed the initial backlog and reconciliation with no failures; subsequent source approval changes processed automatically. Final pending/failed counts checked separately at handoff.

## Scope limits

This is a guided website assistant, not unrestricted language-model conversation or personalized supplement advice. Unreviewed content and unpublished label details intentionally remain unavailable. Desktop QA was the focus; a separate mobile/tablet pass and production supervisor/scheduler setup remain deployment responsibilities.


## Conversational discovery update (2026-10-24)

- Catalog questions explain the current approved categories and offer product, vitamin and mineral browsing. Browsing uses live published/approved listings and bounded ID cursor pagination, with product selection and contextual price/stock/label actions.
- Greetings, thanks and simple acknowledgements use friendly guided replies while preserving selected product context. Unknown requests offer useful next steps instead of exposing confidence terminology.
- Natural question wording is removed from catalog search terms. Ingredient names, vitamin identifiers and strengths remain searchable. Generic “support” no longer routes wellness product searches to Contact Us.
- When product search cannot answer, approved published article bodies are checked for a whole topic phrase and linked as mentions, without inventing health guidance. Topic product follow-up is stored per session and clears on reset/idle expiry.
- Magnesium 250mg is still tagged editorial-draft and is intentionally not offered by the assistant. “Magnesium” now finds the approved label-reading guide which mentions it. Review the product/source through the normal admin approval workflow before enabling its product answers.
- Regression coverage includes catalog wording, browsing pagination, product selection, acknowledgements/context, natural ingredient questions, article-body recovery, withdrawn/unapproved sources and medical precedence. Missing-product fixtures explicitly remove the factory’s default Vitamin D3 facts to test a genuine missing ingredient.
- PHP assistant suite: 85 tests passed (314 assertions). Desktop Chrome checked the reported magnesium/catalog questions and live vitamin browsing and selection. This remains deterministic website guidance with no paid AI API.


## Stress-audit fixes (2026-10-24)


- Validation now returns 422 for array/object/oversized/empty input rather than casting malformed values and producing a server error.
- A dedicated medical guard runs before product discovery, aliases and follow-up actions. The audited condition, allergy, interaction, child and multilingual dosage cases now return a boundary without product cards or shopping actions.
- Urgent/overdose wording has distinct escalation copy. Urgent handling still runs after the ordinary conversation turn cap. Medical/urgent replies invalidate old selected-product and offered-action context.
- Product spacing aliases (co q 10/coqten), common Urdu/Roman Urdu catalog phrasing, capability questions, goal phrasing and article-topic extraction improved.
- Short topic replies retain the ingredient topic; explicit product switching and “no thanks” clear stale topics. An unrelated aside preserves the selected product, while a genuinely missing new product still clears it.
- Vegan/vegetarian/halal/gluten status is explicitly unverified because there is no reviewed certification field. Published benefits are quoted when available and clearly described as website statements; missing details are disclosed.
- Two-product comparisons show current approved product prices and stock, identify the cheaper listed option when asked, and offer product selection. They do not rank medical suitability.
- Order tracking explains that private order data is unavailable and hands off to account/contact. Payment-method and coupon questions disclose the information gap; shipping synonyms retrieve existing published policy.

Verification:
- 94 maintained PHP assistant tests passed, 384 assertions.
- 5 JavaScript widget tests passed.
- The formerly failing external HTTP stress check now passes: 2 tests / 25 assertions, including 422 validation and a 429 response at request 21.
- Replayed all 100 service-level turns, no application exceptions. All 12 medical-risk prompts in the independent medical group return a boundary with no products/actions; the separate narrow “not pregnant + factual price” case returns the actual price. Selected corpus assertions also verify spelling, article recovery, topic continuity, missing Vitamin D3, context across an aside, missing dietary/payment evidence and live comparison.
- Chrome verified the original “Vitamin E with warfarin?” failure now refuses the medical request without product cards; verified the CoQ10/NADH comparison at PKR 2,800 / PKR 3,550.

Limits: this remains a deterministic website assistant, not unrestricted natural-language understanding. Medical detection is conservative and does not cover every possible paraphrase. Unknown dietary certifications, unavailable published label fields, private order tracking and unapproved drafts remain unavailable. Magnesium's editorial-draft restriction was preserved. No paid API, product/source approval change or concurrent production load test was added. Backend-only changes did not require a frontend rebuild.

Raw replay: assistant-stress-results-after.json. Original audit: ASSISTANT-STRESS-REVIEW.md.
