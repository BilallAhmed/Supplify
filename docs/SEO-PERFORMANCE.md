# SEO & Performance Strategy

SEO and performance are **continuous** requirements, with Modules 15–16 as final hardening passes.

---

## SEO Architecture

- Server-rendered Blade for all indexable content (home, shop, PDP, pages, blog)
- Unique URL structure with stable slugs
- Per-entity SEO fields (title, description) with sensible fallbacks
- Global defaults via `settings` / config (site name, OG image)

### Semantic HTML

- One `h1` per page
- Logical heading hierarchy
- Landmarks: `header`, `nav`, `main`, `footer`
- Buttons vs links used correctly
- Product content in HTML, not only images

### URL / Slugs

| Resource | Pattern |
|----------|---------|
| Home | `/` |
| Shop | `/shop` |
| Category | `/shop/{category}` |
| Product | `/products/{slug}` |
| Page | `/pages/{slug}` or top-level reserved (`/about`, `/faq`, …) |
| Blog index | `/blog` |
| Blog post | `/blog/{slug}` |
| Admin | `/admin/*` (noindex) |

Slugs: unique, immutable preferred after publish (or 301 on change).

### Metadata

- `<title>` unique
- meta description unique where possible
- canonical absolute URL
- robots meta for drafts/admin (`noindex, nofollow`)

### Open Graph / Twitter

- `og:title`, `og:description`, `og:image`, `og:url`, `og:type`
- Product pages: product image + price where appropriate
- Blog: `article`

### XML Sitemap & Robots

- Generated sitemap: products, categories, pages, posts
- Exclude admin, cart, checkout, account, API
- `robots.txt` allows public; disallows private paths
- Rebuild sitemap via job on content publish (or scheduled)

### Structured Data (JSON-LD)

- **Organization** / WebSite on home
- **Product** + Offer + availability on PDP
- **BreadcrumbList** on shop/PDP/blog
- **Article** on blog posts
- **FAQPage** if FAQ content structured

Validate with Rich Results tools before launch.

### Internal Linking

- Header/footer nav
- Related products
- Blog ↔ product links where relevant
- Shop-by-goal → category/product

---

## Performance Strategy

### Targets (launch aspirational)

| Metric | Target |
|--------|--------|
| LCP | ≤ 2.5s on mid mobile |
| INP | ≤ 200ms |
| CLS | ≤ 0.1 |
| Homepage JS | Animation code split; not on every page |

### Images

- Upload max dimensions enforced
- Generate WebP (AVIF optional if toolchain easy)
- `srcset` / responsive sizes
- Lazy-load below fold; eager for LCP hero
- Explicit width/height or aspect-ratio to reduce CLS
- Compress without destroying supplement label readability

### Assets

- Vite production build, hashed filenames, minification
- Tailwind purge/content scanning
- Defer non-critical JS
- Homepage GSAP loaded only on homepage route
- Prefer CSS for simple hovers; GSAP for storytelling

### JavaScript Loading

- Alpine/Livewire as needed
- No heavy SPA routers
- Avoid unused libraries

### CSS Strategy

- Utility-first Tailwind design tokens
- Critical layout via standard CSS flow
- Avoid huge custom animation CSS files

### Caching

- Page/fragment caching for homepage sections and category listings where personalization allows
- Config/route/view cache in production
- CDN for static assets
- Product cache invalidated on admin save

### Database

- Indexes per `/docs/DATABASE.md`
- Eager load (`with`) to prevent N+1 on listings/PDP
- Pagination everywhere lists grow
- Avoid `SELECT *` in hot paths when practical
- Conditional stock updates for concurrency

### Queues

- Emails, image processing, sitemap, non-critical AI logging → queue
- Never block checkout response on slow external calls beyond payment necessity

### Animation Performance (GSAP)

- Animate `transform` / `opacity` primarily
- ScrollTrigger with thoughtful start/end; kill instances on navigate if needed
- Respect `prefers-reduced-motion: reduce` → disable or simplify
- Reduce/disable heavy parallax on small viewports
- No continuous infinite animations that burn CPU
- Test mobile FPS; simplify if CWV regresses (quotation mandate)

### Mobile

- Touch-friendly targets
- Avoid hover-only essential actions
- Compress hero media aggressively

---

## Per-Module SEO/Perf Checklist

```text
[ ] Titles/meta/canonical
[ ] Heading structure
[ ] Indexable HTML content
[ ] Schema if applicable
[ ] Images optimized / lazy
[ ] No N+1
[ ] Indexes used by queries
[ ] JS/CSS weight reviewed
[ ] Motion respects reduced-motion
```
