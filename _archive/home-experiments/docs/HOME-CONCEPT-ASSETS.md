# Homepage concept (`/home-concept`) — asset notes

Phase 1 visual prototype. Live homepage at `/` is unchanged.

## Product packaging (permanent catalog assets)

| Role | Product | Path |
|------|---------|------|
| Hero + detail | Magnesium 250mg | `public/images/products/1S5A3686.png` |
| Range 1 | CoQ10 200mg | `public/images/products/1S5A6193.png` |
| Range 2 | Vitamin B-50 Complex | `public/images/products/1S5A6200_1.png` |
| Range 3 | Berberine 500mg | `public/images/products/1S5A3695.png` |

Copy and label facts come from the product records / inventory seed — do not invent claims.

## Temporary lifestyle imagery (replace later)

Kept local and path-swappable in `HomeConceptController` (`$lifestyle` array):

| Scene key | Current file | Origin |
|-----------|--------------|--------|
| `hero_wash` | `images/homepage/hero-lifestyle-magnesium.webp` | Existing Supplify homepage lifestyle set |
| `everyday` | `images/homepage/hero-lifestyle-vitamins.webp` | Existing Supplify homepage lifestyle set |
| `everyday_alt` | `images/homepage/hero/campaign-daily-vitamins-desktop.webp` | Existing campaign desktop art |
| `closing` | `images/homepage/hero-lifestyle-coq10.webp` | Existing Supplify homepage lifestyle set |

When client photography arrives, update only the paths in `HomeConceptController` (or a future config) — Blade keys stay the same.
