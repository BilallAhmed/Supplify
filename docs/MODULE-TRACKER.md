# Module Tracker

Statuses: `NOT STARTED` | `PLANNED` | `IN DEVELOPMENT` | `IN REVIEW` | `QA` | `COMPLETED` | `BLOCKED`

Update this file at the end of every module session.

---

## Module 00 — Project Foundation

| Field | Value |
|-------|-------|
| Objective | Laravel 13 foundation, docs, env, DB connectivity, base folders |
| Dependencies | None |
| Files/components | `docs/*`, `.env`, `.env.example`, `app/{Actions,Services,...}`, routes structure |
| Database changes | Default Laravel migrations only; DB `supplement_ecommerce` |
| Backend work | App bootstrap, Livewire installed |
| Frontend work | Vite/Tailwind/GSAP installed |
| Admin work | Folders only |
| Integrations | None live |
| Security checks | Secrets in env placeholders; no committed credentials |
| SEO checks | N/A |
| Performance checks | N/A |
| Tests | Migrate succeeded; HTTP 200 on `/`; Vite build OK |
| Status | **COMPLETED** |
| Completion notes | Laravel 13.34.0 + Livewire 4.4.7 + GSAP + Tailwind 4. PHP 8.3.33 CLI (winget) used because XAMPP PHP 8.2 cannot run Laravel 13. DB `supplement_ecommerce` on MariaDB. Planning docs complete. Stopped after foundation per brief. |

---

## Module 01 — Design System / UI Foundation

| Field | Value |
|-------|-------|
| Objective | Storefront layout + reusable UI components |
| Dependencies | 00 |
| Files/components | `layouts/storefront`, header/footer partials, Blade UI components, `home` + `/design-system`, `docs/DESIGN-SYSTEM.md` |
| Database changes | None |
| Backend work | `HomeController`, `DesignSystemController` |
| Frontend work | Tailwind theme tokens (botanical), Syne+Figtree, buttons/forms/cards/alerts/modal/nav |
| Admin work | Shared tokens only (admin shell = Module 03) |
| Integrations | — |
| Security checks | Blade escaping; CSRF meta on layout; no secrets |
| SEO checks | Semantic landmarks, title/description/canonical/OG stubs, skip link |
| Performance checks | Limited fonts; no GSAP on layout; reduced-motion base CSS |
| Tests | `DesignSystemTest` — 2 passed |
| Status | **COMPLETED** |
| Completion notes | Visual gallery at `/design-system`. Home is a brand-first shell until Module 06. |

---

## Module 02 — Authentication / Users / Roles

| Field | Value |
|-------|-------|
| Objective | Customer + admin auth |
| Dependencies | 00, 01 |
| Files/components | Auth controllers/requests/views, `EnsureUserHasRole`, profile page, admin placeholder gate |
| Database changes | `users.role/phone/is_active/soft deletes`, `addresses` table |
| Backend work | Register/login/logout/password reset/profile; role middleware alias `role` |
| Frontend work | Auth + profile views on storefront design system; header auth state |
| Admin work | `/admin` requires `auth` + `role:admin` (placeholder dashboard) |
| Integrations | Laravel password broker / mail (log driver locally) |
| Security checks | Role not mass-assignable; rate limits; session regenerate; inactive blocked; CSRF |
| SEO checks | Auth pages use layout meta; admin `noindex` |
| Performance checks | N/A beyond throttle |
| Tests | `AuthenticationTest` — 8 passed |
| Status | **COMPLETED** |
| Completion notes | Seeded `admin@example.com` / `customer@example.com` (password: `password`). Address CRUD deferred to Module 08. |

---

## Module 03 — Admin Foundation

| Field | Value |
|-------|-------|
| Objective | `/admin` shell + gate |
| Dependencies | 02 |
| Files/components | `layouts/admin`, sidebar/topbar, dashboard, module placeholders |
| Database changes | None |
| Backend work | `DashboardController`, `ModulePlaceholderController`; all routes `auth` + `role:admin` |
| Frontend work | Dense admin shell with grouped nav matching quotation modules |
| Admin work | Dashboard widgets (partial live customer count); placeholders for catalog/commerce/content |
| Integrations | — |
| Security checks | Guest redirect; customer 403; admin `noindex` |
| SEO checks | Admin noindex/nofollow |
| Performance checks | Minimal JS (Alpine sidebar only) |
| Tests | `AdminFoundationTest` 4 + auth regression — 12 passed |
| Status | **COMPLETED** |
| Completion notes | Placeholder pages reserve nav for Modules 04–13/19. Login as `admin@example.com` / `password`. |

---

## Module 04 — Products / Categories / Inventory

| Field | Value |
|-------|-------|
| Objective | Catalog domain + admin CRUD |
| Dependencies | 03 |
| Files/components | Models Category/Product/ProductImage/Tag; StockStatus; Money; CatalogService/MediaService; admin category/product/inventory views |
| Database changes | `categories`, `products`, `product_images`, `tags`, `product_tag`, `category_product` |
| Backend work | Admin Category/Product/Inventory controllers; policies; form requests; product duplicate |
| Frontend work | Admin CRUD forms + inventory stock editor (storefront catalog = Module 05) |
| Admin work | Live `/admin/categories`, `/admin/products`, `/admin/inventory` replacing placeholders |
| Integrations | Local disk media via MediaService |
| Security checks | Policies + `role:admin`; Form Requests; base Controller uses `AuthorizesRequests` |
| SEO checks | Admin `noindex` (storefront SEO in Module 05) |
| Performance checks | Eager-load relations on admin indexes |
| Tests | `CatalogAdminTest` — 4 passed |
| Status | **COMPLETED** |
| Completion notes | Money stored in cents. StockStatus syncs from qty. CatalogSeeder provides sample categories/products. Storefront listing/PDP deferred to Module 05. |

---

## Module 05 — Storefront Catalog & PDP

| Field | Value |
|-------|-------|
| Objective | Shop + product detail |
| Dependencies | 01, 04 |
| Files/components | `StorefrontCatalogService`; Shop/Product controllers; `x-product-card`; shop + PDP views; JSON-LD partials |
| Database changes | None (read models only) |
| Backend work | Published listing with search/category/sort/in-stock filters; PDP + related fallback |
| Frontend work | `/shop`, `/shop/{category}`, `/products/{slug}`; filters sidebar; product cards |
| Admin work | — |
| Integrations | — |
| Security checks | Draft/inactive products & categories 404; prices server-rendered |
| SEO checks | Unique title/description/canonical; Product + BreadcrumbList JSON-LD; OG/Twitter meta |
| Performance checks | Eager-loaded images/categories; paginated (12) |
| Tests | `StorefrontCatalogTest` — 4 passed |
| Status | **COMPLETED** |
| Completion notes | Add to cart disabled until commerce modules. Home “Shop by goal” links live categories. |

---

## Module 06 — CMS Pages & Homepage Sections

| Field | Value |
|-------|-------|
| Objective | Pages + structured homepage + GSAP |
| Dependencies | 01, 03, 04 |
| Files/components | Page/HomepageSection/NewsletterSubscriber; ContentService; admin pages + homepage editors; section Blade partials; `resources/js/home.js` |
| Database changes | `pages`, `homepage_sections`, `newsletter_subscribers` |
| Backend work | Pages CRUD; section enable/edit; newsletter subscribe (throttled) |
| Frontend work | Composed homepage from enabled sections; CMS pages at `/pages/{slug}`; light GSAP reveals |
| Admin work | `/admin/pages`, `/admin/homepage` |
| Integrations | — |
| Security checks | Draft pages 404; admin policies; newsletter rate limit |
| SEO checks | Page meta title/description/canonical |
| Performance checks | GSAP loaded only via homepage Vite entry; reduced-motion respected |
| Tests | `CmsContentTest` — 4 passed; `DesignSystemTest` home shell |
| Status | **COMPLETED** |
| Completion notes | Premium homepage: Lenis + GSAP (hero slider/parallax, pinned Approach, horizontal Featured), color-band rhythm, dummy product SVGs via `displayImageUrl()`, CMS slide/beat config. Swap assets/copy when client content arrives. |

---

## Module 07 — Blog / Insights

| Field | Value |
|-------|-------|
| Objective | Blog listing + single |
| Dependencies | 03, 01 |
| Files/components | Post/PostCategory models; BlogService; admin blog CRUD; storefront `/blog` + `/blog/{slug}`; Article JSON-LD; BlogSeeder |
| Database changes | `post_categories`, `posts`, `post_tag` |
| Backend work | Publish workflow, categories/tags, featured image upload, related posts, global search articles |
| Frontend work | Listing + filters, single article, homepage education cards, header search Articles group |
| Admin work | `/admin/blog` resource replacing Module 07 placeholder |
| Integrations | Public disk media for featured images |
| Security checks | Policies + admin FormRequest; draft posts 404 on storefront |
| SEO checks | Meta title/description/canonical; OG article; Article JSON-LD |
| Performance checks | Eager-loaded category/tags/author; paginated listing (9) |
| Tests | `BlogTest` — 4 passed; search regression covers articles |
| Status | **COMPLETED** |
| Completion notes | Seeded sample insights via BlogSeeder. Homepage education section shows latest 3 posts. |

---

## Module 08 — Customer Account

| Field | Value |
|-------|-------|
| Objective | Profile + orders UI |
| Dependencies | 02, 12 (for order history) |
| Files/components | Account dashboard; AccountOrderController; AddressController; account nav; checkout address prefills |
| Database changes | None (uses `addresses`, `orders`) |
| Backend work | Auth account routes; order ownership via policy; address CRUD with default flag |
| Frontend work | `/account` overview, orders list/detail, addresses CRUD, profile shell nav |
| Admin work | — |
| Integrations | — |
| Security checks | `auth` middleware; Order/Address policies; cannot view others’ orders/addresses |
| SEO checks | Account pages transactional |
| Performance checks | Paginated orders (10); recent orders limited to 5 |
| Tests | `CustomerAccountTest`; `AdminCustomerTest` |
| Status | **COMPLETED** |
| Completion notes | Storefront: Header Account icon → dashboard; addresses CRUD; order history. Admin: `/admin/customers` list/show/update (active flag) with addresses + order history links (was placeholder). Default address prefills checkout shipping fields. |

---

## Module 09 — Cart

| Field | Value |
|-------|-------|
| Objective | Cart operations + server totals |
| Dependencies | 05 |
| Files/components | `Cart`/`CartItem` models; `CartService`; storefront cart page; PDP add form; header badge |
| Database changes | `carts`, `cart_items` |
| Backend work | Add/update/remove with stock caps; server-side price recompute; guest↔user merge on login/register |
| Frontend work | `/cart` summary UI; live header count; PDP quantity + add to cart |
| Admin work | — |
| Integrations | — |
| Security checks | CSRF + throttle on mutations; item ownership checks; prices never trusted from client |
| SEO checks | Cart page meta; noindex not required yet (can harden in Module 15) |
| Performance checks | Eager-load products/images for summary; badge composer avoids creating empty carts |
| Tests | `CartTest` — 5 passed |
| Status | **COMPLETED** |
| Completion notes | Checkout CTA disabled until Module 10. Coupon discount slot reserved (`discount_cents = 0`). |

---

## Module 10 — Checkout

| Field | Value |
|-------|-------|
| Objective | Checkout + pending order |
| Dependencies | 09, 13 |
| Files/components | `PlaceOrder` action; `CheckoutService`; Order/OrderItem/OrderStatusHistory; checkout + confirmation views; `config/store.php` |
| Database changes | `orders`, `order_items`, `order_status_histories` |
| Backend work | Guest/auth checkout; server totals (subtotal/discount/shipping/tax); coupon `used_count` increment; cart cleared after place |
| Frontend work | `/checkout` form (contact, shipping, billing same-as); confirmation by order number; cart CTA enabled |
| Admin work | — (order admin = Module 12) |
| Integrations | Flat shipping + optional tax % via `config/store.php` / env (admin settings later) |
| Security checks | Throttled place; confirmation gated by session or owner; prices recomputed server-side; stock validated |
| SEO checks | Checkout/confirmation are transactional (noindex can harden in Module 15) |
| Performance checks | Single DB transaction for place order |
| Tests | `CheckoutTest` — empty cart, place order, free shipping + coupon, tax, validation |
| Status | **COMPLETED** |
| Completion notes | Guest/auth checkout with shipping/tax. **COD** is the interim payment method (Module 11 gateway still TBC): order → processing, payment pending, stock decremented, confirmation email queued. Default shipping $5.99 / free over $75; tax 0% until client rate set. |

---

## Module 11 — Payments

| Field | Value |
|-------|-------|
| Objective | One payment gateway |
| Dependencies | 10 + client merchant account |
| Status | **BLOCKED** (gateway TBC) |
| Completion notes | Interim: **Cash on delivery** wired in checkout (`PaymentMethod::Cod`). Replace/extend with real gateway when client confirms provider. |

---

## Module 12 — Orders & Notifications

| Field | Value |
|-------|-------|
| Objective | Order lifecycle + email |
| Dependencies | 10/11 |
| Files/components | Admin OrderController; `UpdateOrderStatus`; `OrderPlacedNotification`; admin orders index/show |
| Database changes | `orders.payment_method` |
| Backend work | Admin list/filter/detail; status/payment/fulfillment updates + history; queued order email on place |
| Frontend work | `/admin/orders` UI; checkout COD selector + confirmation copy |
| Admin work | Live orders module replacing placeholder |
| Integrations | Mail via queue (`OrderPlacedNotification`); local `MAIL_MAILER=log` |
| Security checks | Admin policy + FormRequest; confirmation still session/owner gated |
| SEO checks | Admin noindex |
| Performance checks | Queued notification; locked stock rows on place |
| Tests | `CheckoutTest` (COD/stock/email) + `OrderAdminTest` |
| Status | **COMPLETED** |
| Completion notes | Customer sees confirmation page + email; admin manages lifecycle. Account order history remains Module 08. |

---

## Module 13 — Coupons / Discounts

| Field | Value |
|-------|-------|
| Objective | Percent/fixed coupons |
| Dependencies | 04; apply in 09/10 |
| Files/components | Coupon model/enum; CouponService; admin coupons CRUD; cart apply/remove; CouponSeeder |
| Database changes | `coupons`; `carts.coupon_id` / `carts.coupon_code` |
| Backend work | Admin CRUD + policies; server-side validation (active, schedule, max uses, min subtotal); discount math in CartService summary |
| Frontend work | Admin coupons UI; cart coupon form + discount line |
| Admin work | Live `/admin/coupons` replacing Module 13 placeholder |
| Integrations | — |
| Security checks | Prices/discounts server-only; throttle on apply; admin FormRequest + policy |
| SEO checks | Cart meta unchanged |
| Performance checks | Coupon resolved once per summary |
| Tests | `CouponTest` — admin create + apply/reject/remove flows |
| Status | **COMPLETED** |
| Completion notes | Seeded `WELCOME10` (10%, min $25) and `SAVE5` ($5 fixed). `used_count` increments deferred to checkout/orders (Module 10/12). |

---

## Module 14 — AI Sticky Chatbot

| Field | Value |
|-------|-------|
| Objective | Grounded sticky assistant |
| Dependencies | 04, 05, content; AI provider |
| Status | **BLOCKED** (provider/model TBC) |
| Completion notes | API costs client-billed; not in PKR 220,000 fee |

---

## Module 15 — SEO Hardening

| Field | Value |
|-------|-------|
| Objective | Final technical SEO audit/pass |
| Dependencies | Public modules complete |
| Status | **PLANNED** |
| Completion notes | |

---

## Module 16 — Performance Optimization

| Field | Value |
|-------|-------|
| Objective | CWV + query/asset pass |
| Dependencies | Feature-complete RC |
| Status | **PLANNED** |
| Completion notes | |

---

## Module 17 — Security Audit

| Field | Value |
|-------|-------|
| Objective | Full checklist + dependency audit |
| Dependencies | RC |
| Status | **PLANNED** |
| Completion notes | |

---

## Module 18 — QA / UAT

| Field | Value |
|-------|-------|
| Objective | Release candidate validation |
| Dependencies | 15–17 |
| Status | **PLANNED** |
| Completion notes | Window 1–6 Nov per plan |

---

## Module 19 — Production / Deployment

| Field | Value |
|-------|-------|
| Objective | Live stable before 11.11 |
| Dependencies | UAT approved |
| Status | **PLANNED** |
| Completion notes | Deploy 7–9 Nov; freeze 10 Nov. Admin store settings live at `/admin/settings` (currency, shipping method/flat/free-over, tax %) in `store_settings`, used by checkout. Tracking & Scripts at `/admin/settings/tracking` (GTM/GA4/Meta/custom snippets, consent + env gates, `settings_audits`). Full production deploy checklist still pending. |
