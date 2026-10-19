# Development Standards

Goal: code another senior Laravel developer can maintain without archaeology.  
Prefer clarity over cleverness. **Do not overengineer** a 15–100 product store.

---

## Laravel Conventions

- Follow current Laravel 13 structure and naming
- Prefer framework features (Form Requests, Policies, Notifications, Jobs) before custom frameworks
- Keep `routes/*.php` thin — point to controllers or Livewire full-page components
- Config in `config/` + `.env`; never hardcode secrets

## PHP Standards

- PHP 8.3+ features welcome (`readonly`, enums, typed properties)
- PSR-12 via Laravel Pint
- Strict types optional project-wide; be consistent within a file
- No `dd()`, `dump()`, `var_dump()`, `console.log` debug left in merged code

## Naming

| Type | Convention |
|------|------------|
| Classes | `PascalCase` |
| Methods/vars | `camelCase` |
| DB | `snake_case` |
| Config keys | `snake_case` |
| Livewire | `CreateProduct`, `CartDrawer` |
| Actions | Verb phrase: `PlaceOrder`, `ApplyCoupon` |
| Enums | `OrderStatus`, `UserRole` |

## Classes & Organization

- **Controllers:** HTTP concerns only — authorize, validate (via Form Request), call action/service, return response
- **Actions:** one business operation, invokable or `execute()`
- **Services:** multi-step integrations (Payment, AI, Media)
- **Models:** relationships, casts, scopes; avoid fat unrelated logic
- **Form Requests:** all input validation rules + authorize when useful
- **Policies:** resource authorization
- **Jobs:** async work; idempotent when possible
- **Events/Listeners:** decoupling (OrderPaid → SendEmail, DecrementStock already transactional — be careful)
- **Notifications:** user-facing mail/database
- **Enums:** statuses and fixed option sets
- **Support:** tiny pure helpers only

## Avoid

- Repository interfaces with one Eloquent implementation “for SOLID”
- God services that do everything
- Copy-paste coupons/pricing logic — centralize in Pricing/Cart services
- Premature microservices
- Unnecessary package additions without a written reason

## Blade / Livewire / Alpine

- Blade components for reusable UI (`<x-button>`, `<x-product-card>`)
- Livewire for interactive forms, cart, admin CRUD, chat widget transport
- Alpine for small UI state (dropdowns, modals) when Livewire round-trip is unnecessary
- Keep Livewire properties validated; don’t trust public props for prices
- Extract GSAP into `resources/js/animations/*` and import only where needed

## Database / Migrations

- One concern per migration when practical
- Always reversible `down()` when safe
- Foreign keys + indexes in same migration as table
- Money as integer cents
- Soft deletes only when business needs undelete/audit
- Seeders for local demo; never assume seed data in production

## Error Handling

- Domain exceptions for expected business failures (e.g. `InsufficientStockException`)
- Controllers convert to user-friendly flash/JSON errors
- Report unexpected exceptions; don’t swallow

## Logging

- `Log::info/warning/error` with context arrays
- Never log secrets or card data
- Tag payment/webhook channels

## Comments / Documentation

- Comment **why**, not what
- Update `/docs/MODULE-TRACKER.md` when module status changes
- Public API of services should be obvious from method names

## Git / Commits

- Atomic commits per logical change
- Imperative subject: `Add product admin CRUD`
- Do not commit `.env`, keys, `node_modules`, built secrets
- Do not commit unless the user requests a commit

## Duplication vs Abstraction

- Three similar lines → maybe extract
- One speculative future use → don’t extract yet
- Shared pricing math → must be single source of truth

## Testing Standards

- Feature tests for auth, cart totals, checkout, coupons, webhooks, policies
- Unit tests for pure pricing/inventory helpers
- Name tests as behaviors: `test_guest_cannot_access_admin`

## Definition of Done Reminder

Functionality, validation, authorization, security, responsive UI, SEO (if public), performance, errors, tests, cleanup, docs/tracker.

## Priority Stack (when tradeoffs appear)

**Security → Performance → Maintainability → SEO → Scalability → UX → Development Speed**
