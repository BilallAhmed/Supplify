@if ($tracking['show_banner'] ?? false)
    @php
        $consentCookie = (string) config('tracking.consent_cookie', 'supplify_consent');
        $consentDays = (int) config('tracking.consent_cookie_days', 365);
    @endphp
    <div
        class="consent-banner"
        role="dialog"
        aria-labelledby="consent-banner-title"
        aria-describedby="consent-banner-desc"
        x-data="{
            cookie: @js($consentCookie),
            days: {{ $consentDays }},
            write(payload) {
                const maxAge = Math.max(1, this.days) * 24 * 60 * 60;
                const value = encodeURIComponent(JSON.stringify(payload));
                const secure = window.location.protocol === 'https:' ? '; Secure' : '';
                document.cookie = `${this.cookie}=${value}; Path=/; Max-Age=${maxAge}; SameSite=Lax${secure}`;
                window.location.reload();
            },
            acceptAll() {
                this.write({ analytics: true, marketing: true, decided: true });
            },
            rejectNonEssential() {
                this.write({ analytics: false, marketing: false, decided: true });
            }
        }"
    >
        <div class="consent-banner__inner">
            <div class="min-w-0">
                <h2 id="consent-banner-title" class="consent-banner__title">Cookies &amp; tracking</h2>
                <p id="consent-banner-desc" class="consent-banner__desc">
                    We use analytics and marketing cookies to understand storefront traffic and measure campaigns.
                    Essential cookies keep the site working and do not require consent.
                </p>
            </div>
            <div class="consent-banner__actions">
                <x-button type="button" variant="outline" size="sm" @click="rejectNonEssential()">Reject non-essential</x-button>
                <x-button type="button" variant="primary" size="sm" @click="acceptAll()">Accept all</x-button>
            </div>
        </div>
    </div>
@endif
