# Modules & Execution Sequence

## Final Chosen Sequence

```text
00 Project Foundation          ← current (complete after verification)
01 Design System / UI Foundation
02 Authentication / Users / Roles
03 Admin Foundation
04 Products / Categories / Inventory
05 Storefront Catalog & Product Detail
06 CMS Pages & Homepage Sections
07 Blog / Insights
09 Cart
13 Coupons (wire into cart/checkout; admin CRUD can start after 04)
10 Checkout
11 Payments                    ← blocked until gateway confirmed
12 Orders & Notifications
08 Customer Account (order history after 12; auth shell earlier in 02)
14 AI Sticky Chatbot           ← blocked until AI provider confirmed
15 SEO Hardening Pass
16 Performance Optimization Pass
17 Security Audit
18 QA / UAT
19 Production / Deployment
```

### Why this order (vs a pure frontend-first or backend-first approach)

1. **Foundation → Design system** gives consistent UI for every later module.  
2. **Auth → Admin → Catalog** matches quotation week 7–13 Oct and lets the client (or us) enter products early.  
3. **Storefront before cart** so PDP/add-to-cart has real pages.  
4. **Homepage CMS after products** so featured/shop-by-goal use real data.  
5. **Blog parallelizable** after CMS patterns exist.  
6. **Cart → Checkout → Payments → Orders** is a hard dependency chain.  
7. **Customer order history** after orders exist (account shell can reuse Module 02).  
8. **AI after catalog + content** so grounding data exists.  
9. **SEO/Perf/Security hardening** as dedicated finish passes *in addition to* continuous practice.  
10. **Deploy before 11 Nov**, freeze on 10 Nov.

Cross-cutting SEO, security, and performance are applied **during** each module, with Modules 15–17 as final audits.

---

## Module Catalog

### Module 00 — Project Foundation

| | |
|--|--|
| **Purpose** | Laravel 13 app, env, DB, tooling, docs, base structure |
| **Features** | Install, MySQL config, Vite/Tailwind, Livewire, GSAP, folder layout, planning docs |
| **Dependencies** | None |
| **Database** | Default Laravel tables only |
| **Backend** | AppServiceProvider baseline |
| **Frontend** | Default welcome until Module 01 |
| **Admin** | None yet |
| **Security** | `.env` secrets pattern, APP_DEBUG local only |
| **SEO/Perf** | N/A |
| **Tests** | App boots; migrate succeeds |
| **DoD** | App runs on `artisan serve`; docs present; DB connected |
| **Status** | IN DEVELOPMENT → COMPLETE after verification |

### Module 01 — Design System / UI Foundation

| | |
|--|--|
| **Purpose** | Reusable visual system for storefront (and admin tokens) |
| **Features** | Typography, spacing, buttons, forms, cards, header/footer shells, alerts, modals, breakpoints |
| **Dependencies** | 00 |
| **Database** | None |
| **Backend** | View composers if needed |
| **Frontend** | Blade components + Tailwind theme |
| **Admin** | Shared form styles optionally |
| **Security** | Escape by default in Blade |
| **SEO** | Semantic landmark regions in layout |
| **Performance** | Minimal JS; font strategy |
| **Tests** | Component render smoke |
| **DoD** | Layouts + core components documented and usable |

### Module 02 — Authentication / Users / Roles

| | |
|--|--|
| **Purpose** | Customer + admin identity |
| **Features** | Register, login, logout, password reset, role field, profile basics |
| **Dependencies** | 00, 01 |
| **Database** | users.role, addresses (optional here or with account) |
| **Backend** | Auth controllers/Livewire, middleware `role` |
| **Frontend** | Auth views |
| **Admin** | Admin login uses same users with role check |
| **Security** | Rate limits, CSRF, hashed passwords, session config |
| **Tests** | Feature tests auth flows |
| **DoD** | Customer can auth; admin blocked from customer-only routes and vice versa |

### Module 03 — Admin Foundation

| | |
|--|--|
| **Purpose** | Admin shell + dashboard overview scaffold |
| **Features** | `/admin` layout, nav, auth gate, empty dashboard widgets |
| **Dependencies** | 02 |
| **Database** | None new required |
| **Backend** | Admin middleware group |
| **Frontend** | Admin layout |
| **Security** | Role middleware on all admin routes |
| **DoD** | Only admins access `/admin` |

### Module 04 — Products / Categories / Inventory

| | |
|--|--|
| **Purpose** | Catalog domain + admin CRUD |
| **Features** | Full product fields per quotation, categories, images, related, stock, SEO fields |
| **Dependencies** | 03 |
| **Database** | categories, products, pivots, images, relations |
| **Backend** | Models, policies, actions, media service |
| **Frontend** | None required yet (admin UI primary) |
| **Admin** | Full product/category/inventory management |
| **Security** | Upload validation, authorize all mutations |
| **SEO** | Slug uniqueness, meta fields stored |
| **Performance** | Indexed queries; image derivatives |
| **Tests** | CRUD + stock update tests |
| **DoD** | Admin can manage ~15 products end-to-end |

### Module 05 — Storefront Catalog & PDP

| | |
|--|--|
| **Purpose** | Public shop experience |
| **Features** | Shop, category pages, search, filters, sort, cards, PDP, related |
| **Dependencies** | 01, 04 |
| **Database** | Read models only |
| **Backend** | Storefront controllers/Livewire |
| **Frontend** | Shop + PDP templates |
| **SEO** | Titles, canonicals, product schema, breadcrumbs |
| **Performance** | Eager load images/categories; pagination |
| **DoD** | Browse and view products on mobile/desktop |

### Module 06 — CMS Pages & Homepage Sections

| | |
|--|--|
| **Purpose** | Structured content + homepage |
| **Features** | Pages CRUD; homepage sections enable/disable/edit; newsletter CTA wiring |
| **Dependencies** | 03, 04 (for featured products), 01 |
| **Database** | pages, homepage_sections, newsletter_subscribers |
| **Frontend** | Homepage composition + GSAP hooks |
| **Admin** | Section editors |
| **SEO** | Page meta |
| **Performance** | GSAP conditional load; reduced motion |
| **DoD** | Homepage editable without deploy; GSAP polished but light |

### Module 07 — Blog / Insights

| | |
|--|--|
| **Purpose** | Content marketing |
| **Features** | Listing, single, categories/tags, SEO, related |
| **Dependencies** | 03, 01 |
| **Database** | posts + taxonomy |
| **DoD** | Publish post visible on storefront with meta |
| **Status** | COMPLETED |

### Module 08 — Customer Account

| | |
|--|--|
| **Purpose** | Account area |
| **Features** | Profile, addresses, order list/detail |
| **Dependencies** | 02; orders UI after 12 |
| **DoD** | Logged-in customer sees profile and orders |
| **Status** | COMPLETED |

### Module 09 — Cart

| | |
|--|--|
| **Purpose** | Persistent cart behavior |
| **Features** | Add/update/remove, totals, inventory checks, guest+auth merge |
| **Dependencies** | 05 |
| **Security** | Server-side price recompute |
| **DoD** | Cart accurate across refresh/login |
| **Status** | COMPLETED |

### Module 10 — Checkout

| | |
|--|--|
| **Purpose** | Collect info + create pending order |
| **Features** | Forms, validation, summary, coupon apply, shipping basic rules |
| **Dependencies** | 09, 13 (coupon apply) |
| **DoD** | Pending order created with correct totals |
| **Status** | COMPLETED — pending orders; payment in Module 11 |

### Module 11 — Payments

| | |
|--|--|
| **Purpose** | One gateway integration |
| **Features** | Charge/intent, webhooks, failure handling |
| **Dependencies** | 10 + client gateway credentials |
| **Security** | See SECURITY.md payment section |
| **DoD** | Paid test order + idempotent webhook |
| **Status** | BLOCKED — interim **COD** enabled until gateway confirmed |

### Module 12 — Orders & Notifications

| | |
|--|--|
| **Purpose** | Order lifecycle |
| **Features** | Statuses, admin management, emails |
| **Dependencies** | 11 (or 10 for unpaid flows) |
| **DoD** | Customer + admin see order; email queued |
| **Status** | COMPLETED — admin orders + queued confirmation email (COD flow) |

### Module 13 — Coupons / Discounts

| | |
|--|--|
| **Purpose** | Basic percent/fixed codes |
| **Features** | Admin CRUD, usage limits, validity, cart/checkout apply |
| **Dependencies** | 04 for admin; 09/10 for apply |
| **DoD** | Valid coupon changes totals; invalid rejected server-side |
| **Status** | COMPLETED — admin CRUD + cart apply; usage count increments at checkout (Module 10/12) |

### Module 14 — Supplify Assistant (website-only)

| | |
|--|--|
| **Purpose** | Product-aware shopping assistant using approved website content only |
| **Features** | Guided intents, structured response cards, live price/stock later, sticky widget later, admin approval/FAQ |
| **Dependencies** | 04, 05, CMS pages/blog; **no** external AI API |
| **Security** | Explicit knowledge approval; rate limits; medical boundaries; no private orders/profiles |
| **DoD** | Real eligible product/page links only; honest unknowns; refuses diagnosis/prescription |
| **Status** | COMPLETED — website-only guided assistant (Layers 1–7); dual-gate enablement; no AI API |

### Module 15 — SEO Hardening

Sitemap, robots, OG audit, schema audit, crawl check.

### Module 16 — Performance Optimization

CWV pass, image/cache/query audit, animation budget.

### Module 17 — Security Audit

Full SECURITY.md checklist + dependency audit.

### Module 18 — QA / UAT

Functional, responsive, browser, commerce, chatbot, regression.

### Module 19 — Production / Deployment

Env, SSL, workers, backups, monitoring, handover.

---

## Continuous Practices (every module)

- Security review  
- SEO for public surfaces  
- Performance awareness  
- Tracker update in `MODULE-TRACKER.md`  
