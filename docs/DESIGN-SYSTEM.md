# Design System (Module 01)

## Visual direction

Clinical-premium botanical brand system for a US supplement storefront.

| Token | Role |
|-------|------|
| Plus Jakarta Sans | Display / brand headings |
| Figtree | Body UI text |
| Brand green `#2A5C4A` | Primary actions & identity |
| Accent warm metal `#B08D57` | Secondary emphasis |
| Surface mist | Atmospheric page background (gradient, not flat white) |

Avoid one-off colors in feature modules — extend tokens in `resources/css/app.css` `@theme` when needed.

## Layout

| Piece | Path |
|-------|------|
| Storefront layout | `resources/views/layouts/storefront.blade.php` |
| Header | `resources/views/storefront/partials/header.blade.php` |
| Footer | `resources/views/storefront/partials/footer.blade.php` |
| Home shell | `resources/views/storefront/home.blade.php` |
| Component gallery | `/design-system` |

## Components

| Component | Usage |
|-----------|--------|
| `<x-button>` | `variant`: primary, secondary, outline, ghost, accent, danger · `size`: sm, md, lg · optional `href` |
| `<x-input>` | Label, help, error, name |
| `<x-textarea>` | Same pattern |
| `<x-select>` | Options as slot |
| `<x-checkbox>` | Label + help |
| `<x-label>` | Standalone label |
| `<x-card>` | Interactive/content panels · `hover` optional |
| `<x-alert>` | info/success/warning/danger · `dismissible` |
| `<x-modal>` | `name`, `title`, optional `footer` slot · open via `$dispatch('open-modal-{name}')` |
| `<x-badge>` | Status chips |
| `<x-container>` | Page width shell |
| `<x-section-heading>` | Section title block |

## Accessibility

- Skip link to `#main-content`
- Semantic `header` / `nav` / `main` / `footer`
- Focus-visible outlines
- `prefers-reduced-motion` respected in base CSS
- Modals: Escape + backdrop dismiss, `aria-modal`

## Performance notes

- Fonts loaded from Bunny Fonts (Figtree + Plus Jakarta Sans only)
- Alpine provided via Livewire scripts on storefront layout
- GSAP not loaded on these pages (homepage motion = Module 06)
