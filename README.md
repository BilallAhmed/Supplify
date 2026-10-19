# Supplify (Phase 1)

Custom US supplement e-commerce platform — Laravel 13 + Blade/Livewire + MySQL + GSAP.

**Do not use WordPress or Shopify.**  
**Business source of truth:** client quotation PDF.  
**Technical plans:** `/docs`

## Quick start (local XAMPP)

Requirements:

- PHP **8.3+** (Laravel 13). This project was bootstrapped with PHP 8.3.33 via winget; XAMPP’s PHP 8.2 is insufficient for Artisan.
- Composer, Node.js, npm
- MySQL/MariaDB (XAMPP) with database `supplement_ecommerce`

```bash
# Ensure PHP 8.3 is first on PATH for this shell
composer install
copy .env.example .env   # if needed
php artisan key:generate
php artisan migrate
php artisan storage:link
npm install
npm run build

# IMPORTANT: use port 8080 (port 8000 is often used by OpenJarvis)
php artisan serve --host=127.0.0.1 --port=8080
# Or double-click serve.bat
```

App URL: **http://127.0.0.1:8080**

> Do not use `http://127.0.0.1:8000` for this project — that port conflicts with OpenJarvis and can show a cached SPA shell until a hard refresh.

## Documentation

| Doc | Purpose |
|-----|---------|
| `docs/PROJECT_PLAN.md` | Roadmap, milestones, DoD |
| `docs/ARCHITECTURE.md` | System architecture |
| `docs/DATABASE.md` | Schema plan |
| `docs/MODULES.md` | Module sequence |
| `docs/MODULE-TRACKER.md` | Live status tracker |
| `docs/SECURITY.md` | Security checklist |
| `docs/SEO-PERFORMANCE.md` | SEO & performance |
| `docs/DEVELOPMENT-STANDARDS.md` | Coding standards |
| `docs/QUOTATION-SPEC.md` | Quotation extraction |

## Development rule

Implement **one module at a time** (vertical slices). Update `MODULE-TRACKER.md` after each module.

## Secrets

Never commit real `.env` credentials, payment keys, or AI API keys.
