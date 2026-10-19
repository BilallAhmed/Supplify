# Quotation → Technical Specification

Extracted from `Supplement_Ecommerce_Development_Quotation.pdf`.  
**Rule:** Do not silently remove or invent features. Interpretations are marked explicitly.

---

## Project Identity

| Field | Value |
|-------|--------|
| Type | Custom D2C US supplement e-commerce |
| Stack mandate | Laravel 13.x, Blade + Livewire + Alpine, MySQL, GSAP, Vite |
| Catalog | ~15 launch products; architecture for 100+ |
| Budget | PKR 220,000 fixed Phase 1 |
| Campaign | 11 November 2026 |
| Forbidden platforms | WordPress, Shopify, pre-built e-commerce themes |

---

## Feature Inventory by Domain

### Public storefront

| Feature | Notes |
|---------|--------|
| Premium homepage | Hero, storytelling, featured products, shop-by-goal, promos, trust, testimonials*, education, newsletter |
| GSAP motion | Hero reveals, scroll reveals, product card micro-interactions, reduced-motion support |
| Shop listing | Pagination, categories, sort, search, essential filters |
| Product detail | Full supplement fields + gallery + related + SEO |
| Cart | Qty CRUD, subtotal, discounts |
| Checkout | Customer/shipping/billing, coupons, payment, confirmation |
| Customer account | Auth, profile, password reset, orders |
| Static/CMS pages | About, Contact, FAQ, Shipping, Returns, Privacy, Terms |
| Blog listing + single | Featured image, excerpt, categories/tags, SEO |
| Site search | Products + relevant content; keep simple for ~15–100 SKUs |
| Responsive UX | Desktop/tablet/mobile; a11y-conscious; reduced motion |

\*Testimonials only where supplied by client.

### Customer account

Registration, login, logout, password reset, profile, basic account management, order list, order detail.

### Commerce engine

Products, categories, tags, pricing, sale pricing, stock, related/featured, cart, coupons, checkout, one payment gateway, orders, basic shipping rules, client-supplied tax rules.

### Admin dashboard

Dashboard overview, products, categories, orders, customers, inventory, coupons, homepage sections, pages, blogs, media. **No** visual page builder.

### CMS / content

Structured homepage sections (enable/disable/edit), standard pages, blog posts. Design/content services included; client approves legal copy.

### AI chatbot

Sticky widget, grounded Q&A, product recommendations + real product URLs, session context, fallbacks, mobile UI. Provider TBC. No medical diagnosis/prescription/unsupported claims. API costs excluded.

### SEO

Meta foundations, canonical, robots, sitemap, image optimization considerations (bundled in SEO/performance package).

### Security

Essential hardening (bundled). Production e-commerce practices required.

### Performance

Caching considerations, image optimization, controlled animation weight, Core Web Vitals awareness.

### Deployment / infrastructure

Production deployment + handover docs/training + 1-month free minor support. Hosting/CDN/domain client-owned. Nginx+PHP-FPM+HTTPS+CDN/WAF recommended.

---

## Integrations

| Integration | Phase 1 | Notes |
|-------------|---------|--------|
| One primary payment gateway | Yes | Provider **TBC**; merchant account required |
| AI provider API | Yes (assistant) | Provider/model **TBC**; usage costs client-billed |
| Transactional email | Likely required | Provider **TBC**; not a separate fee line but needed for order mail |
| Redis | Production preferred | Local: database drivers |
| Object storage | Optional | As needed |
| SMS/WhatsApp | No | Unless separately requested |
| Maps APIs | No | |
| ERP/CRM | No | Phase 2 |

---

## Deliverables

- Launch-ready custom storefront + admin + AI assistant  
- Included design, images/assets, content services  
- QA, deployment, walkthrough, documentation/tutorials, training  
- 1 month free post-launch minor support  

---

## Exclusions

Subscriptions, advanced bundles, loyalty, advanced AI engines, ERP/CRM, advanced shipping/tax automation, native apps, complex marketing automation, legal/regulatory consulting, bulk manual catalog upload beyond ~15, third-party recurring fees.

---

## Interpretation Decisions (recommended)

| Open point | Recommended approach |
|------------|----------------------|
| Payment gateway | Abstract `PaymentGateway` interface; implement one official SDK once client confirms (Stripe common for US; do not hard-code until confirmed) |
| AI provider | `AiProvider` interface + server-side chat service; OpenAI/Anthropic/etc. swappable; never expose API keys to JS |
| Tax | Store settings + simple rule table from client inputs; no TaxJar/Avalara in Phase 1 |
| Shipping | Flat/basic rule set (e.g. flat rate / free over threshold) configurable in admin settings |
| Auth | Laravel session auth; `role` enum or simple roles table (`customer`, `admin`) — Spatie Permission only if multi-role complexity appears (prefer simple for Phase 1) |
| Alpine | Use Livewire’s bundled Alpine; avoid duplicate Alpine npm install unless needed |
| Search | MySQL `LIKE` / fulltext on products & posts for Phase 1; upgrade later if needed |
| Media | `storage/app/public` + intervention/optimization pipeline; WebP conversion on upload |
| Currency | USD storefront default; confirm with client |
| Admin URL | `/admin` with auth + role middleware (not security-by-obscurity alone) |

---

## Dependency Highlights

- Products before storefront merchandising, cart, AI recommendations  
- Cart before checkout before payments before fulfilled order history  
- Auth before admin and customer account  
- Homepage featured products need product module  
- AI needs product + FAQ/page content  
- Payment module blocked on client merchant approval  
