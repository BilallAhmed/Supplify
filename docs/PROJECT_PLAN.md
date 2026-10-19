# Project Plan — Custom US Supplement E-commerce Platform

**Source of truth:** `Supplement_Ecommerce_Development_Quotation.pdf`  
**Phase:** Phase 1 / 11.11 Launch Scope  
**Fixed fee:** PKR 220,000 (development + included design/content/assets)  
**Target campaign:** 11 November 2026  
**Production readiness target:** on or before **9 November 2026**  
**Document status:** Approved technical baseline for vertical module development

---

## 1. Project Overview

A fully custom US-focused D2C supplement e-commerce platform built with Laravel — **not** WordPress, Shopify, or a pre-built theme.

The application combines:

- A fast, SEO-friendly public storefront
- A premium animated homepage (GSAP)
- Customer accounts and end-to-end commerce
- A purpose-built admin dashboard (not an Elementor-like page builder)
- A basic AI website assistant grounded in approved product/site content

Initial catalog ≈ **15 products**; architecture must support **100+** without redesign.

**Reference direction (inspiration only, not copies):** Thorne, Tonik, AVEA Life, existing Lahore site (mysupplementandmore.com).

---

## 2. Business Objectives

1. Deliver a premium modern supplement brand experience for the US market.
2. Enable 11.11 campaign readiness with a stable production release beforehand.
3. Give the client practical day-to-day control of products, orders, inventory, content, and homepage sections.
4. Build product/commerce architecture once so catalog growth does not require redevelopment.
5. Include a practical AI product-discovery assistant without medical advice scope.
6. Keep Phase 1 within PKR 220,000 while excluding enterprise features (subscriptions, loyalty, ERP, advanced tax/shipping automation, etc.).

---

## 3. Functional Scope (from Quotation)

### In scope — Public storefront

- Homepage: hero, storytelling, featured products, shop-by-goal/category, promos, benefits/trust, testimonials (if supplied), education/brand story, newsletter/CTA, responsive layout, GSAP motion
- Shop/catalog: listing, pagination, categories, sorting, search, essential filters
- Product detail: gallery, SKU, price/sale price, description, benefits, ingredients, supplement facts, directions, warnings, stock, add to cart, related products, SEO fields
- Cart: add/remove/update, subtotal, discounts, checkout handoff
- Checkout: customer/shipping/billing, order summary, coupons, one primary payment gateway, confirmation
- Customer account: register/login/password reset/profile/order list/detail
- Content pages: About, Contact, FAQ, Shipping, Returns, Privacy, Terms (+ similar)
- Blog: listing + single post with SEO fields
- Search/discovery for products and relevant content
- Responsive UX + `prefers-reduced-motion` awareness

### In scope — AI Website Assistant

- Sticky chat launcher on key storefront pages
- Answers from approved website/FAQ/policy/product data
- Product-aware recommendations with clickable product links
- Session context within active chat
- Fallback when knowledge is insufficient
- Mobile-friendly UI with loading/error states
- **Must not** diagnose, prescribe, or invent unsupported medical claims
- AI provider/model **TBC** (client); API usage costs **excluded** from development fee

### In scope — Admin dashboard

- Overview: orders, sales indicators, product count, low-stock, recent activity
- Products CRUD (incl. duplicate, publish, media, SEO, related)
- Categories
- Orders (view/detail/status/payment/fulfillment/notes)
- Customers (list/profile/order history/status)
- Inventory (qty, status, low-stock, SKU)
- Coupons (percentage or fixed, usage limits, validity)
- Homepage structured sections (enable/disable/edit — **no** drag-drop page builder)
- Pages CMS
- Blogs CMS
- Media management with validation

### Excluded (Phase 2 candidates)

Subscriptions, advanced bundles, loyalty, advanced AI recommendation engines, ERP/CRM integrations, multi-carrier shipping automation, advanced tax automation, mobile apps, complex marketing automation, regulatory/legal consulting.

### Client responsibilities / open decisions (timeline risks)

| Item | Status |
|------|--------|
| Payment gateway + merchant account | **TBC** |
| AI provider/model | **TBC** |
| Hosting/domain access | Client-provided |
| Currency/tax rules for US store | Client inputs |
| Product data, brand assets, legal approvals | Client |
| Transactional email provider | **TBC** |

---

## 4. Technical Scope

| Layer | Decision |
|-------|----------|
| Backend | Laravel **13.x** (current stable per quotation) |
| Frontend | Blade + Livewire + Alpine.js (via Livewire) |
| Database | MySQL 8 / MariaDB-compatible (XAMPP local: MariaDB 10.4) |
| Animation | GSAP + ScrollTrigger |
| Build | Vite + Tailwind CSS 4 |
| Caching/queues | Database drivers locally; **Redis** in production where available |
| Production infra | Nginx + PHP-FPM + HTTPS + CDN/WAF (client hosting) |
| Media | Local/public disk now; object storage optional later |

**Local PHP note:** Laravel 13 requires PHP **8.3+**. XAMPP ships PHP 8.2; PHP 8.3.33 was installed via winget for CLI/`artisan serve`. Production must use PHP 8.3+.

---

## 5. Recommended Architecture

**Monolithic Laravel app** with server-rendered Blade/Livewire storefront and admin.

- Vertical slice development (module → DB → logic → UI → tests → DoD)
- Thin controllers; Actions/Services only where logic is non-trivial
- Policies for authorization
- Form Requests for validation
- Server-side price/inventory/discount/payment truth
- AI and payment integrations behind Laravel services (no secret keys in frontend JS)
- No SPA/API-first split for Phase 1 (quotation explicitly rejects unnecessary React SPA overhead)

See `/docs/ARCHITECTURE.md` for detail.

---

## 6. Development Methodology

1. **Vertical modules** — complete one module before the next.
2. **Quotation-locked scope** — no silent feature invention or removal.
3. **Security-first** — treat as production e-commerce from day one.
4. **Continuous SEO / performance** — not end-of-project bolt-ons.
5. **Definition of Done** gate before advancing modules.
6. **Client confirmation gates** for payment gateway and AI provider before those modules.

---

## 7. Module Breakdown (summary)

| ID | Module | Notes |
|----|--------|-------|
| 00 | Project Foundation | ✅ This phase |
| 01 | Design System / UI Foundation | Next recommended |
| 02 | Auth / Users / Roles | Admin + customer auth base |
| 03 | Admin Foundation | Layout, auth guard, dashboard shell |
| 04 | Products / Categories / Inventory | Catalog core |
| 05 | Storefront Catalog & PDP | Shop + product pages |
| 06 | CMS Pages & Homepage Sections | Structured content |
| 07 | Blog / Insights | Listing + single |
| 08 | Customer Account | Profile + orders views (orders after commerce) |
| 09 | Cart | Session/DB cart |
| 10 | Checkout | Order creation |
| 11 | Payments | One gateway (TBC) |
| 12 | Orders & Notifications | Status + emails |
| 13 | Coupons / Discounts | Admin + checkout apply |
| 14 | AI Sticky Chatbot | Provider TBC |
| 15 | SEO Hardening | Cross-cutting finish pass |
| 16 | Performance Optimization | Cross-cutting finish pass |
| 17 | Security Audit | Cross-cutting finish pass |
| 18 | QA / UAT | Release candidate |
| 19 | Production / Deployment | Live before 11.11 |

Full detail: `/docs/MODULES.md` and `/docs/MODULE-TRACKER.md`.

---

## 8. Module Dependencies

```text
00 Foundation
 → 01 Design System
 → 02 Auth/Roles
 → 03 Admin Foundation
 → 04 Products/Categories/Inventory
 → 05 Storefront Catalog/PDP
 → 06 CMS/Homepage ─┬→ (can parallelize lightly with 07)
 → 07 Blog           ┘
 → 09 Cart → 10 Checkout → 11 Payments → 12 Orders
 → 08 Customer Account (order history depends on 12)
 → 13 Coupons (can start after 04; wire into 09/10)
 → 14 AI Chatbot (needs 04+05 content)
 → 15 SEO → 16 Performance → 17 Security → 18 QA → 19 Deploy
```

**Chosen sequence rationale:** Auth and admin before catalog so data entry tools exist early (matches quotation week of 7–13 Oct). Storefront before cart. Homepage CMS can follow catalog so featured products are real. Payment blocked until client gateway confirmation.

---

## 9. Milestones & 11.11 Launch Schedule

Aligned to quotation delivery plan; **do not** plan completion for campaign day itself.

| Period | Focus | Output |
|--------|--------|--------|
| 5–6 Oct | Discovery, architecture, foundation | Docs + Laravel foundation ✅ |
| 7–13 Oct | Admin foundation + products/categories | Working back-office + data model |
| 14–20 Oct | Storefront + PDP + catalog | Core shopping experience |
| 21–26 Oct | Cart, checkout, accounts, orders | End-to-end purchase flow |
| 27–31 Oct | Homepage motion + CMS + integrations | Premium homepage + content controls |
| 1–3 Nov | QA, responsive, performance/security | Release candidate |
| 4–6 Nov | Client UAT + fixes | Approved launch build |
| **7–9 Nov** | Production deploy + verification | **Live stable build** |
| 10 Nov | Launch freeze / final checks | Campaign-ready |
| 11 Nov | 11.11 sale | Campaign day |

### Launch readiness checklist (pre-10 Nov)

- [ ] Development freeze complete
- [ ] Payment live/sandbox verification passed
- [ ] Final content & legal pages client-approved
- [ ] SSL, backups, monitoring confirmed
- [ ] Performance pass (Core Web Vitals targets)
- [ ] Security checklist signed off
- [ ] AI assistant knowledge + disclaimers verified
- [ ] Rollback plan documented

---

## 10. Definition of Done (every module)

A module is complete only when all apply:

| Check | Required |
|-------|----------|
| Functionality | ✅ |
| Validation | ✅ |
| Authorization | ✅ |
| Security | ✅ |
| Responsive UI | ✅ |
| SEO (public modules) | ✅ |
| Performance review | ✅ |
| Error handling | ✅ |
| Testing | ✅ |
| Code cleanup (no debug leftovers) | ✅ |
| Documentation / tracker update | ✅ |

---

## 11. QA Process

1. Developer self-test against module DoD  
2. Feature tests (PHPUnit) for critical paths  
3. Manual responsive checks (desktop/tablet/mobile)  
4. Browser smoke (Chromium + Safari/WebKit where available)  
5. Security checklist review (`/docs/SECURITY.md`)  
6. SEO/performance checklist (`/docs/SEO-PERFORMANCE.md`)  
7. Client UAT (4–6 Nov) with consolidated feedback  
8. Regression pass after UAT fixes  

Critical commerce tests: cart totals, coupon math, inventory reservation, payment webhooks (idempotent), order emails.

---

## 12. Deployment Strategy

**Local (now):** PHP 8.3 CLI + `php artisan serve` + Vite; MySQL via XAMPP MariaDB; database cache/queue/session.

**Production (target):** Nginx + PHP-FPM 8.3+, MySQL 8, Redis (if available), HTTPS, CDN/WAF (e.g. Cloudflare), queue worker + scheduler, object storage optional, transactional email provider.

Document deployment runbook in Module 19. Client provides hosting/domain access.

---

## 13. Launch Strategy

1. Deploy stable build by **7–9 Nov**  
2. Soft verification of checkout with real gateway (small live test)  
3. Content freeze + launch freeze **10 Nov**  
4. Monitor logs/errors on campaign day; no feature deploys  
5. 1-month free post-launch support per quotation (minor fixes only)

---

## 14. Post-Launch Considerations

- 1-month free support: minor fixes/config/clarifications only  
- Phase 2 candidates: subscriptions, loyalty, advanced AI, ERP, etc.  
- Structured product import if catalog grows operationally  
- Redis promotion when infrastructure permits  
- Monitor AI token costs (client-billed)

---

## 15. Payment Terms (business reference)

| Milestone | % | Amount |
|-----------|---|--------|
| Commencement | 40% | PKR 88,000 |
| Core storefront + admin | 30% | PKR 66,000 |
| UAT / staging approval | 20% | PKR 44,000 |
| Production launch | 10% | PKR 22,000 |

---

## Related Documents

- `/docs/ARCHITECTURE.md`
- `/docs/DATABASE.md`
- `/docs/MODULES.md`
- `/docs/MODULE-TRACKER.md`
- `/docs/SECURITY.md`
- `/docs/SEO-PERFORMANCE.md`
- `/docs/DEVELOPMENT-STANDARDS.md`
- `/docs/QUOTATION-SPEC.md` (extracted technical specification)
