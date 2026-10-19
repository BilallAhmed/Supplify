# Security Checklist

Treat this as a **production e-commerce** application from the first line of code.  
Complete relevant items per module; full audit in Module 17.

---

## Authentication

- [ ] Passwords hashed with Laravel hasher (bcrypt/argon) — never plaintext
- [ ] Password reset tokens expire and are single-use
- [ ] Login rate limited
- [ ] Registration rate limited
- [ ] Session regenerated on login
- [ ] Logout invalidates session
- [ ] `remember_me` only if intentionally enabled and understood
- [ ] Email enumeration mitigated where practical (generic reset responses)

## Authorization

- [ ] Policies/gates for admin resources
- [ ] Admin routes behind `auth` + `role:admin`
- [ ] Customers cannot access other users’ orders by ID tampering
- [ ] No “security through obscurity” alone for `/admin`

## Roles / Permissions

- [ ] Explicit `role` (customer/admin) checked server-side
- [ ] Default role on register = customer
- [ ] Cannot self-elevate to admin via mass assignment

## Session Security

- [ ] `SESSION_ENCRYPT` considered for production
- [ ] Secure cookie flags in production (`https`)
- [ ] Sensible `SESSION_LIFETIME`
- [ ] SameSite cookie defaults appropriate

## CSRF

- [ ] All state-changing web routes use CSRF
- [ ] Livewire CSRF intact
- [ ] Webhook routes excluded carefully and verified by signature instead

## XSS

- [ ] Blade `{{ }}` for untrusted output
- [ ] `{!! !!}` only for trusted, sanitized HTML (admin CMS — sanitize)
- [ ] CSP considered for production

## SQL Injection

- [ ] Eloquent/query builder only — no raw unescaped user input
- [ ] Bound parameters for any raw queries

## Mass Assignment

- [ ] `$fillable` / `$guarded` explicit
- [ ] Never pass entire request to `create` without filtering
- [ ] Role, price, stock not client-writable on storefront

## Input Validation

- [ ] Form Requests for all writes
- [ ] Server-side validation of quantities, coupons, addresses
- [ ] File MIME + extension + size checks

## Output Escaping

- [ ] JSON APIs encode properly
- [ ] Email templates escape user content

## Rate Limiting

- [ ] Login / register / password reset
- [ ] Checkout submit
- [ ] Chat endpoint
- [ ] Contact forms
- [ ] Webhooks (per IP + signature)

## File Upload Security

- [ ] Whitelist image types
- [ ] Store outside executable context (`storage`)
- [ ] Random/safe filenames
- [ ] No SVG execution risk without sanitization (prefer raster for products)
- [ ] Image re-encoding where practical

## Admin Security

- [ ] Strong admin passwords policy
- [ ] Audit sensitive actions (orders/refunds) as needed
- [ ] Separate session awareness for admin area

## API Security

- [ ] Chat API authenticated by session/CSRF + rate limit
- [ ] No public admin APIs without auth
- [ ] Minimal error detail in production responses

## Webhook Verification

- [ ] Verify provider signatures
- [ ] Idempotent processing (`payment_webhook_events`)
- [ ] Reject replayed/old events when possible
- [ ] HTTPS only in production

## Payment Security

- [ ] **Never** store raw card numbers
- [ ] Secrets only in env / secret manager
- [ ] Official SDK only
- [ ] Server-side payment verification
- [ ] Do not trust frontend payment success alone
- [ ] No payment secrets in JS bundles or logs

## Commerce Integrity

- [ ] Prices from DB, not request
- [ ] Discounts recalculated server-side
- [ ] Inventory checked/decremented transactionally
- [ ] Prevent oversell with conditional updates

## Secrets Management

- [ ] `.env` not committed
- [ ] `.env.example` has empty placeholders only
- [ ] AI keys server-side only
- [ ] Rotate keys on leak suspicion

## Encryption

- [ ] `APP_KEY` set and backed up
- [ ] Encrypt sensitive columns only if required (avoid unnecessary crypto complexity)

## Logging

- [ ] No passwords, tokens, PAN, full card payloads
- [ ] Redact API keys
- [ ] Payment logs store IDs/status, not secrets

## Error Handling

- [ ] `APP_DEBUG=false` in production
- [ ] Generic 500 pages for users
- [ ] Detailed errors only in logs

## Production Configuration

- [ ] HTTPS enforced
- [ ] Secure headers (HSTS, X-Frame-Options, etc.)
- [ ] Trusted proxies configured for Cloudflare/load balancer
- [ ] Directory listing disabled
- [ ] `storage` and `vendor` not publicly executable incorrectly

## Dependency Security

- [ ] `composer audit` / `npm audit` before launch
- [ ] Keep Laravel/Livewire patched
- [ ] No abandoned payment packages

## AI Assistant Security

- [ ] Provider key server-side
- [ ] Prompt injection hardened (system instructions + output filtering)
- [ ] Refuse medical diagnosis/treatment
- [ ] Product URLs only from DB
- [ ] Rate limit chat
- [ ] Do not exfiltrate env/secrets via model tools (no unconstrained tools)

---

## Module Sign-off Template

```text
Module: __
Date: __
Reviewer: __
AuthZ: ☐  Validation: ☐  Uploads: ☐  Secrets: ☐  Logging: ☐
Notes:
```
