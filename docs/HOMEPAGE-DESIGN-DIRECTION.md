# Homepage Design Direction

**Status:** Blueprint only — do not treat this as implemented.  
**Date:** 2026-10-05  
**Project:** Supplify (Laravel storefront, Module 06 CMS foundations)  
**Goal:** Premium motion-driven wellness e-commerce homepage that feels alive while remaining fast, accessible, and CMS-compatible.

---

## 1. Audit summary (current state)

### What exists
| Area | Finding |
|------|---------|
| Route | `/` → `HomeController` → `storefront.home` |
| CMS | `homepage_sections` keyed (`hero`, `story`, `featured_products`, `shop_by_goal`, `promo`, `trust`, `testimonials`, `education`, `newsletter`); admin at `/admin/homepage` |
| Data | Featured products from `is_featured`; categories for goals; newsletter POST `/newsletter` |
| Motion | GSAP + ScrollTrigger only in `resources/js/home.js` / `animations/home.js` — uniform fade/translateY on every section |
| Tokens | Botanical system: brand `#2A5C4A`, accent `#B08D57`, mist/deep surfaces, Plus Jakarta + Figtree |
| Assets | **No product photography in seed/storage** — cards fall back to letter placeholders |
| Stack | Vite, Tailwind 4, Livewire/Alpine, GSAP 3.15. **No Lenis yet** |

### Visual diagnosis (live `/`)
- Hero is brand-name + abstract gradient card — **no product focal point**
- Page reads as stacked light panels with soft borders
- Motion language is one pattern repeated (opacity + Y)
- “Our approach” is static copy + bullet list
- Featured formulas is a standard 4-up card grid
- Promo / trust / testimonials / education feel like filler blocks after the catalog modules
- Marketing copy still leaks implementation (“Editable homepage sections — no redeploy needed”)

### Architecture constraints (non-negotiable)
- Keep Module 06 CMS section enable/edit/sort
- Keep product/category/page routes and SEO meta
- Do not break cart/checkout/admin (cart still placeholder)
- Prefer modular Blade + modular JS under existing folders
- Homepage Vite entry stays separate from global `app.js`

---

## 2. Reference synthesis (inspiration only — do not copy)

| Brand | Steal the idea | Reject the copy |
|-------|----------------|-----------------|
| **Thorne** | Full-bleed hero with product as hero object; science-over-hype trust cadence; multi-slide campaign hero | Athlete lifestyle photography language; dense mega-nav; “certified for sport” identity |
| **Ritual** | Editorial whitespace; traceability as design system; calm premium UI | Heavy serif logo system; subscription-first funnel |
| **AVEA** | Outcome-led merchandising; formula-as-campaign hero; Swiss/science credibility blocks | Longevity-clinic aesthetic; quiz-dominant CTA |
| **Seed** | Single-SKU storytelling depth; science made beautiful; restrained palette | Single-product funnel (we are multi-SKU) |
| **Tonik / Resonance** | Botanical warmth, ingredient-led visuals, quieter DTC energy | Any 1:1 layout clone |

**Extracted principles for us**
1. Product bottle/pack is the hero, not a decorative card.
2. Alternating surface temperature (mist → botanical deep → cream → elevated → deep CTA).
3. Trust is progressive storytelling, not three equal cards.
4. Merchandising feels editorial, not catalog-grid-first.
5. Motion supports product understanding — never replaces content.

---

## 3. Brand visual direction (original)

### Positioning line
> Clinical-premium botanical: clean formulas, transparent labels, calm confidence.

### Color rhythm (extend existing tokens — do not invent a second brand)
| Role | Token direction | Usage |
|------|-----------------|-------|
| Page air | `surface` `#F3F6F4` | Default page canvas |
| Soft panels | `brand-mist` / cream-leaning accent soft | Editorial light bands |
| Elevated commerce | `surface-elevated` white | Product cards, forms |
| Botanical depth | `surface-deep` `#1A2E27` + `brand` | Promo / newsletter / approach mid-band |
| Metal accent | `accent` `#B08D57` | Sparse highlights, sale, magnetic CTA edge |
| Ink | `ink` / muted / subtle | Typography hierarchy |

**Page rhythm (intended)**
```text
Hero (mist → soft botanical wash)
↓
Approach (deep botanical pinned story)
↓
Featured formulas (light elevated editorial track)
↓
Shop by goal (mist, quieter)
↓
Trust marquee + proof (cream / soft)
↓
Social proof (elevated)
↓
Education teaser (mist)  [light until Module 07]
↓
Newsletter (deep)
```

Avoid: purple SaaS gradients, neon, glass stacks, endless white bands, terracotta+cream AI cliché.

### Typography
- Keep **Plus Jakarta Sans** for display; **Figtree** for UI/body.
- Hero headlines: tighter tracking, larger scale, editorial line breaks (benefit-led, not brand-name as H1 forever).
- CMS can still supply title/body; seed copy should sell outcomes (“Formulas you can read.”) not the store name alone.

### Imagery strategy (critical gap)
Until real packshots exist:
1. Prefer uploaded product `images` when present.
2. Seed/admin path: attach primary images to featured SKUs.
3. Temporary art direction: soft botanical still-life plates generated/sourced as **brand-owned** placeholders (not competitor packs), with clear alt text.
4. Hero slides should bind to **product IDs** in section `config` so CMS stays the source of truth.

---

## 4. Motion language

### Stack
| Layer | Choice | Why |
|-------|--------|-----|
| Smooth scroll | **Lenis** (homepage only) | Fluid feel without full-page snap |
| Scroll animation | **GSAP ScrollTrigger** synced to Lenis via `scrollerProxy` / Lenis `raf` | Already in project |
| Micro-interactions | GSAP + CSS transforms | No AOS / Locomotive / Framer |

### Rules
- Prefer `transform` + `opacity` only.
- One strong interaction per major section — not every effect everywhere.
- Pointer-only features: hero parallax, magnetic CTAs, card spotlight.
- `prefers-reduced-motion: reduce` → disable Lenis smoothing, pin scrub, parallax; keep layout + content.
- Kill ScrollTriggers on Turbo/Livewire navigations if introduced later; for now init once on `home.js`.

### Forbidden default
Repeating only:
```text
opacity 0 → 1 + translateY
```
across every section.

---

## 5. Section-by-section plan

### A. Hero — **REPLACE** (highest priority)
**CMS:** Expand `hero.config` to slides array:
```json
{
  "slides": [
    {
      "eyebrow": "...",
      "title": "...",
      "body": "...",
      "cta_label": "...",
      "cta_url": "/shop",
      "secondary_cta_label": "...",
      "secondary_cta_url": "#approach",
      "product_id": 1,
      "theme": "mist"
    }
  ]
}
```
Fallback: if no slides, derive 2–3 from featured products + section fields.

**Layout:** Full viewport; left copy; right floating product stack (product + soft shadow + optional secondary layer).

**Motion**
- Cinematic slide change: clip-path / horizontal wipe + product scale/offset + text stagger (not plain fade).
- Cursor parallax on product layers (lerp), desktop `pointer: fine` only.
- Autoplay with pause on hover/focus; accessible prev/next + dots; `aria-live` for slide title.

**Mobile:** Stack product above or below copy; disable cursor parallax; swipe optional via buttons first.

---

### B. Our Approach (`story`) — **REWORK → scroll storytelling**
Rename UX to “Our Approach” while keeping CMS key `story` for compatibility (or alias in view).

**Narrative beats (config-driven):**
`Science → Quality → Transparency → Results`  
Each beat: title, short body, optional icon key.

**Motion:** Desktop pinned section; progress scrub changes active beat, advances a connecting line, crossfades a side visual (ingredient plate / label crop / bottle detail). Mobile: horizontal snap carousel or vertical stacked steps without pin.

**Do not** ship three static equal cards as the final design.

---

### C. Featured Formulas — **REPLACE merchandising pattern**
Keep data source (`featuredProducts`).

**Desktop:** Horizontal scroll-linked track (vertical scroll drives X translate) with large editorial cards (image dominant, name, short positioning from `short_description`, price, CTA). Active card emphasis via scale/opacity.

**Mobile:** Native horizontal scroll-snap OR simplified 1.2-card peek carousel — no confusing pin.

**Card micro-interactions:** Image shift, soft spotlight (pointer), shadow lift, CTA reveal. No heavy 3D.

---

### D. Shop by goal — **RETAIN content, UPGRADE presentation**
Keep category links. Larger outcome tiles, subtle mask reveal on scroll, quieter motion than Featured.

---

### E. Promo — **COMBINE / SLIM**
Either merge into hero slide #3 (campaign) or keep as thin deep botanical band with one CTA. Avoid competing with Featured.

---

### F. Trust — **REWORK**
Replace 3-card grid with:
1. Slow value marquee (one line, pausable, reduced-motion static), plus
2. Compact proof row (3 statements) with count-up only if content justifies it.

---

### G. Testimonials — **RETAIN, UPGRADE**
Editorial quote stage; soft horizontal progress or fade with different motion than Featured (no duplicate carousel feel).

---

### H. Education — **KEEP LIGHT**
Until Module 07 blog exists: compact teaser linking to `/blog` placeholder or hide via CMS `is_enabled` if empty. Do not invent fake articles.

---

### I. Newsletter — **RETAIN, POLISH**
Deep section, magnetic subscribe button (desktop), clear success state (already flash-based).

---

## 6. Animation storyboard (scroll order)

| Beat | Trigger | Action |
|------|---------|--------|
| 0 | Load | Hero text stagger; product floats in with soft scale; Lenis starts |
| 1 | Hero idle | Product parallax follows pointer (fine) |
| 2 | Hero slide | Clip wipe + product swap + headline morph |
| 3 | Approach enter | Pin; line draws; beat 1 active |
| 4 | Approach scrub | Beats 2–4; visual crossfade |
| 5 | Featured enter | Track arms; first cards emphasize |
| 6 | Featured scrub | Horizontal progress; active card focus |
| 7 | Goals | Masked tile reveal (stagger X, not only Y) |
| 8 | Trust | Marquee starts; proof fades via clip |
| 9 | Quotes | Quote scale/track subtle |
| 10 | Newsletter | Deep panel rises; magnetic CTA |

---

## 7. Component & JS architecture (planned)

### Blade (adapt under existing tree)
```text
resources/views/storefront/home/
  home.blade.php
  sections/…          # keep CMS key mapping
components/homepage/
  hero-slider.blade.php
  approach-timeline.blade.php
  featured-rail.blade.php
  goal-tile.blade.php
  trust-marquee.blade.php
  testimonial-stage.blade.php
  newsletter-band.blade.php
```

### JS
```text
resources/js/
  home.js                 # orchestrator
  animations/
    smooth-scroll.js      # Lenis + ScrollTrigger bridge
    hero.js
    approach.js
    featured-formulas.js
    interactions.js       # magnetic, spotlight helpers
```

Add dependency: `lenis` only (homepage bundle). Do not add Locomotive/AOS/Framer.

---

## 8. CMS compatibility plan

| Need | Approach |
|------|----------|
| Hero slides | `hero.config.slides[]` with product_id |
| Approach beats | `story.config.beats[]` |
| Featured limit | existing `featured_products.config.limit` |
| Marquee phrases | `trust.config.marquee[]` + keep `items` |
| Enable/disable | existing `is_enabled` / `sort_order` |
| Admin UX | Extend homepage editor with clearer JSON helpers or structured fields in a later pass — Phase D can keep JSON with documented schema |

Do **not** create a parallel hardcoded homepage that ignores `/admin/homepage`.

---

## 9. Mobile strategy

- Lenis lighter touch multiplier or disabled if jank detected.
- No hero cursor parallax; no magnetic buttons.
- Approach: no long pin — stepped vertical or snap carousel.
- Featured: scroll-snap rail with scrub optional.
- Guard against horizontal overflow (`overflow-x: clip` on main).
- CTAs always visible without hover dependence.

---

## 10. Performance budget

| Item | Target |
|------|--------|
| Homepage JS (home.js gzip) | Prefer &lt; ~60KB extra beyond GSAP; tree-shake Lenis |
| Images | Responsive `srcset`, WebP, lazy below fold; hero LCP image `fetchpriority=high` |
| CLS | Reserve product/frame aspect boxes |
| Motion | No layout thrashing; batch reads/writes; one rAF loop via GSAP/Lenis |
| SEO | Real H1/H2 text in DOM; product links crawlable; no content only in canvas |

---

## 11. Accessibility

- Slider: buttons, keyboard, focus rings, `aria-roledescription="carousel"`
- Reduced motion path tested
- Marquee: `aria-hidden` decorative duplicate text or pause control
- Pin sections: ensure focus order still logical
- Contrast on deep botanical bands (white / soft mist text)

---

## 12. What we will NOT do

- Copy Thorne/Ritual/AVEA layouts or photography
- Full-page scrolljacking / forced snap between all sections
- 50 micro-animations with no hierarchy
- Multiple competing scroll libraries
- Hard-code homepage so CMS cannot edit
- Break shop/PDP/admin/auth
- Ship letter-only product heroes as “final” without an image plan

---

## 13. Implementation phases (execution order after approval)

| Phase | Deliverable | Exit criteria |
|-------|-------------|---------------|
| **D** | Lenis bridge, token rhythm utilities, component shells | Smooth scroll + reduced motion OK |
| **E** | Hero slider + product parallax | 2–3 slides, a11y, mobile OK |
| **F** | Approach pinned story | 4 beats, mobile fallback |
| **G** | Featured editorial rail | Products link; mobile snap |
| **H** | Goals / trust / quotes / newsletter polish | Varied motion; CMS intact |
| **I** | Responsive QA | No overflow; touch OK |
| **J** | Perf pass | Images, JS, ScrollTrigger cleanup |
| **K** | Full QA checklist | Browsers + reduced motion |

---

## 14. Open decisions for stakeholder

1. **Hero copy:** Benefit-led headlines per slide vs brand-name H1 (recommend benefit-led H1, brand in eyebrow).
2. **Product photography:** Upload packshots for Omega / Magnesium before hero polish, or approve temporary brand still-life assets.
3. **Approach pin length:** Aggressive storytelling vs shorter pin (recommend ~120–160% viewport scrub).
4. **Promo section:** Merge into hero slide vs keep slim band (recommend merge if hero has 3 slides).

---

## 15. Success criteria

The homepage should communicate:

> Premium US botanical supplement brand

Users should notice: strong hero product presence, fluid scrolling, interactive approach story, editorial featured merchandising, cohesive color rhythm, restrained micro-interactions, and fast response — without feeling gimmicky or trapped by scroll effects.
