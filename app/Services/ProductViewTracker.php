<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductView;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class ProductViewTracker
{
    public function __construct(
        private readonly StoreSettings $settings,
    ) {}

    public function record(Product $product, Request $request): bool
    {
        if (! config('analytics.product_views.enabled', true)) {
            return false;
        }

        if (config('analytics.product_views.exclude_admins', true) && $this->isAdminPreview($request)) {
            return false;
        }

        if (config('analytics.product_views.exclude_bots', true) && $this->isAutomatedTraffic($request)) {
            return false;
        }

        $visitorKey = $this->visitorKey($request);
        if ($visitorKey === null) {
            return false;
        }

        $now = now();
        $dedupeSeconds = max(0, (int) config('analytics.product_views.dedupe_seconds', 30));

        if ($dedupeSeconds > 0) {
            $cacheKey = sprintf('analytics.product_view.%s.%d', $visitorKey, $product->id);
            // Atomic short-window lock: refresh / duplicate requests do not double-count.
            if (! Cache::add($cacheKey, 1, $dedupeSeconds)) {
                return false;
            }
        }

        ProductView::query()->create([
            'product_id' => $product->id,
            'visitor_key' => $visitorKey,
            'viewed_at' => $now,
        ]);

        $this->ensureTrackingStarted($now);

        return true;
    }

    public function trackingStartedAt(): ?Carbon
    {
        $key = (string) config('analytics.product_views.started_at_key', 'analytics.product_views_started_at');
        $raw = $this->settings->get($key);

        if (! is_string($raw) || $raw === '') {
            $first = ProductView::query()->orderBy('viewed_at')->value('viewed_at');

            return $first ? Carbon::parse($first)->timezone(config('app.timezone')) : null;
        }

        return Carbon::parse($raw)->timezone(config('app.timezone'));
    }

    private function ensureTrackingStarted(Carbon $at): void
    {
        $key = (string) config('analytics.product_views.started_at_key', 'analytics.product_views_started_at');

        if ($this->settings->get($key)) {
            return;
        }

        $this->settings->putMany([
            $key => $at->clone()->timezone(config('app.timezone'))->toIso8601String(),
        ]);
    }

    private function visitorKey(Request $request): ?string
    {
        if (! $request->hasSession()) {
            return null;
        }

        if (! $request->session()->isStarted()) {
            $request->session()->start();
        }

        // Persist an anonymous key in the session so it remains stable even if the
        // underlying session id rotates. Never stores IP, email, or other PII.
        $existing = $request->session()->get('analytics.visitor_key');
        if (is_string($existing) && strlen($existing) === 64 && preg_match('/^[a-f0-9]+$/', $existing)) {
            return $existing;
        }

        $key = hash_hmac(
            'sha256',
            $request->session()->getId().'|'.bin2hex(random_bytes(16)),
            (string) config('app.key')
        );

        $request->session()->put('analytics.visitor_key', $key);

        return $key;
    }

    private function isAdminPreview(Request $request): bool
    {
        $user = $request->user();

        return $user instanceof User && $user->isAdmin();
    }

    private function isAutomatedTraffic(Request $request): bool
    {
        $ua = strtolower((string) $request->userAgent());

        if ($ua === '') {
            return true;
        }

        return (bool) preg_match(
            '/bot|crawl|spider|slurp|scrapy|curl\/|wget|python-requests|httpclient|headless|phantom|selenium|puppeteer|facebookexternalhit|preview|monitor|uptime|pingdom|statuscake/i',
            $ua
        );
    }
}
