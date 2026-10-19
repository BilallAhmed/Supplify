# Architecture

## 1. Application Architecture

**Style:** Modular monolithic Laravel 13 application.

```text
Browser (Storefront / Admin)
        │
        ▼
Nginx/Apache or php artisan serve
        │
        ▼
Laravel HTTP Kernel (middleware, CSRF, auth)
        │
   ┌────┴────┐
   ▼         ▼
Blade/Livewire   API/Webhooks (payments, optional)
   │
   ▼
Actions / Services / Models
   │
   ▼
MySQL  ◄──►  Cache/Queue (DB local | Redis prod)
   │
   ▼
External: Payment Gateway | AI Provider | Mail
```

**Principles**

- Server-rendered HTML for SEO-critical pages
- Livewire for interactive islands (cart qty, filters, admin forms, chat widget transport)
- Alpine for lightweight client behavior
- GSAP only on marketing/homepage surfaces
- Secrets and commerce math stay server-side
- Vertical module ownership over horizontal “frontend then backend”

---

## 2. Laravel Application Structure

```text
app/
├── Actions/           # Single-purpose use cases (PlaceOrder, ApplyCoupon, …)
├── Console/
├── Enums/             # OrderStatus, PaymentStatus, StockStatus, UserRole, …
├── Events/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   ├── Auth/
│   │   ├── Storefront/
│   │   └── Api/       # Webhooks only where needed
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/     # Optional JSON for chat/admin APIs
├── Jobs/
├── Listeners/
├── Livewire/
│   ├── Admin/
│   ├── Chat/
│   └── Storefront/
├── Models/
├── Notifications/
├── Policies/
├── Providers/
├── Services/          # Payment, AI, Pricing, Inventory, Cart, Seo, Media
└── Support/           # Small helpers, money, slug utilities

resources/
├── css/
├── js/
│   └── animations/    # GSAP modules
└── views/
    ├── admin/
    ├── auth/
    ├── components/
    ├── emails/
    ├── layouts/
    └── storefront/

routes/
├── web.php            # Storefront
├── admin.php          # Admin (loaded with prefix/middleware)
├── auth.php           # Auth routes
└── api.php            # Webhooks / chat endpoint
```

Avoid repository interfaces unless a clear swap is required. Prefer Eloquent + Actions.

---

## 3. Frontend Architecture

| Concern | Approach |
|---------|----------|
| Templates | Blade layouts + components |
| Interactivity | Livewire 4 (+ Alpine) |
| Styling | Tailwind CSS 4 via Vite |
| Motion | GSAP + ScrollTrigger; gate with `matchMedia` + `prefers-reduced-motion` |
| Assets | Vite code-splitting; homepage animation JS loaded only on homepage |
| Images | Responsive `srcset`, WebP/AVIF where generated, lazy-load below fold |

Admin UI shares design tokens where practical but uses denser admin layout — not a clone of the marketing site.

---

## 4. Database Architecture

MySQL/MariaDB with InnoDB, `utf8mb4`, bigint PKs, explicit FKs, soft deletes where recovery matters (products, users, posts), hard deletes for cart lines/ephemeral data.

See `/docs/DATABASE.md`.

---

## 5. Authentication Architecture

- Laravel session authentication
- Guards: `web` for customers and admins (single users table + `role`)
- Password hashing: bcrypt (Laravel default / `BCRYPT_ROUNDS`)
- Password reset via Laravel notifications
- Admin routes: `auth` + `role:admin` middleware
- Rate limiting on login, register, password reset, checkout, chat, webhooks

---

## 6. Admin Architecture

- Prefix `/admin`
- Separate Blade layout `layouts.admin`
- Livewire components per resource for CRUD efficiency
- Structured section editors for homepage — field-based, not a page builder
- Policies for every mutating admin action

---

## 7. Service / Integration Architecture

```text
App\Services\Payment\PaymentGatewayManager
App\Services\Payment\Drivers\{Stripe,AuthorizeNet, …}  // one implemented

App\Services\Ai\AiChatService
App\Services\Ai\Providers\{OpenAiCompatible, …}

App\Services\Cart\CartService
App\Services\Pricing\PricingService
App\Services\Inventory\InventoryService
App\Services\Media\MediaService
App\Services\Seo\SeoService
```

Drivers selected via `.env` (`PAYMENT_GATEWAY`, `AI_PROVIDER`).

---

## 8. AI Chatbot Architecture

```text
Sticky Livewire/Alpine widget
        │  POST /api/chat (CSRF + rate limit)
        ▼
AiChatService
  ├─ loads product/FAQ/page knowledge snippets (DB)
  ├─ builds system prompt with hard medical disclaimers
  ├─ calls AiProvider (server-side key)
  ├─ parses product IDs from model output
  └─ resolves product URLs from DB (never trust invented URLs)
        │
        ▼
Response: markdown/text + validated product link cards
```

- Session context stored server-side (cache/DB), not relying on client alone  
- If unknown → explicit fallback  
- Costs: client API account  

---

## 9. Payment Architecture

```text
Checkout → Create Order (pending)
        → PaymentService::charge / redirect / intent
        → Webhook (signature verified, idempotent)
        → Update payment_status + order_status
        → Decrement inventory in transaction
        → Queue confirmation email
```

Never store PAN/CVV. Never trust frontend “paid” flags.

---

## 10. File / Media Architecture

- Upload via validated Form Requests (MIME, size, dimensions)
- Store on `public` disk (local) → symlink `storage`
- Generate derivatives (thumb, card, zoom) + WebP
- Attach via `media` / `product_images` tables
- Production may switch disk to S3-compatible without model changes

---

## 11. Caching Architecture

| Env | Cache | Session | Queue |
|-----|-------|---------|-------|
| Local XAMPP | `database` | `database` | `database` |
| Production | Redis preferred | Redis preferred | Redis preferred |

Cache keys for: category trees, homepage sections, product pages (invalidate on save), settings.

---

## 12. Queue / Job Architecture

Use queues for: emails, image processing, sitemap rebuild, AI non-critical logging, webhook post-processing.

Worker required in production (`queue:work` supervised).

---

## 13. Logging / Monitoring Architecture

- Laravel `stack` channel; `daily` in production  
- Never log secrets, card data, raw AI API keys, full payment payloads with PII beyond need  
- Production: error monitoring (e.g. provider TBC — Flare/Sentry) + uptime check  
- Payment/webhook channels tagged for audit  

---

## 14. Production Infrastructure

Per quotation:

- Nginx + PHP-FPM 8.3+  
- MySQL 8  
- HTTPS  
- CDN/WAF (Cloudflare or host equivalent)  
- Redis when available  
- Scheduler cron + queue worker  
- Backups (DB + media)  
- Env secrets outside web root  

Local: XAMPP Apache optional; primary verified path is `php artisan serve` + Vite with PHP 8.3 CLI.

---

## Architecture Decision Record (ADR) — Key Choices

1. **No React SPA** — quotation budget/timeline and SEO favor Blade/Livewire.  
2. **Simple roles over Spatie initially** — two roles suffice; add package only if needed.  
3. **Payment/AI abstracted** — providers TBC.  
4. **Homepage CMS = structured sections** — not a page builder.  
5. **Redis optional locally** — database drivers keep XAMPP simple.  
