@php
    /** @var array<string, mixed> $contact */
    $banner = $contact['banner'] ?? [];
    $form = $contact['form'] ?? [];
    $details = $contact['details'] ?? [];
    $mail = $contact['mail'] ?? [];
    $usePlaceholders = filter_var(old('contact.details.use_placeholders', $details['use_placeholders'] ?? true), FILTER_VALIDATE_BOOLEAN);
    $notificationsEnabled = filter_var(old('contact.mail.notifications_enabled', $mail['notifications_enabled'] ?? false), FILTER_VALIDATE_BOOLEAN);
    $contactErrorOpen = fn (string $prefix) => collect($errors->keys())->contains(
        fn ($k) => str_starts_with((string) $k, $prefix)
    );
@endphp

<div class="admin-panel admin-panel--sections space-y-5">
    <div>
        <h2 class="admin-panel__title">Contact page sections</h2>
        <p class="admin-panel__intro">
            Editorial content for the dedicated Contact template. Displayed contact details are separate from email notification settings.
            Leave placeholders enabled until genuine contact details are confirmed.
        </p>
    </div>

    <details class="admin-section-details rounded-[var(--radius-control)] border border-line bg-surface-elevated p-3" open>
        <summary class="cursor-pointer font-semibold text-ink">1. Banner</summary>
        <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <x-input label="Eyebrow" name="contact[banner][eyebrow]" :value="old('contact.banner.eyebrow', $banner['eyebrow'] ?? '')" />
            <div class="sm:col-span-2">
                <x-input label="Heading" name="contact[banner][heading]" :value="old('contact.banner.heading', $banner['heading'] ?? '')" />
            </div>
            <div class="sm:col-span-2">
                <x-textarea label="Introduction" name="contact[banner][intro]" rows="3">{{ old('contact.banner.intro', $banner['intro'] ?? '') }}</x-textarea>
            </div>
            <div class="sm:col-span-2">
                <x-media-picker
                    name="contact[banner][image]"
                    label="Banner image"
                    :value="old('contact.banner.image', $banner['image'] ?? '')"
                    help="Default asset: images/contact/contact-hero.webp"
                />
            </div>
            <x-input
                label="Image alt"
                name="contact[banner][image_alt]"
                :value="old('contact.banner.image_alt', $banner['image_alt'] ?? '')"
            />
        </div>
    </details>

    <details class="admin-section-details rounded-[var(--radius-control)] border border-line bg-surface-elevated p-3" open>
        <summary class="cursor-pointer font-semibold text-ink">2. Form</summary>
        <div class="mt-3 space-y-3">
            <x-input label="Heading" name="contact[form][heading]" :value="old('contact.form.heading', $form['heading'] ?? '')" />
            <x-textarea label="Introduction" name="contact[form][intro]" rows="2">{{ old('contact.form.intro', $form['intro'] ?? '') }}</x-textarea>
            <x-input
                label="Privacy note (storefront uses fixed Privacy Policy link)"
                name="contact[form][privacy_note]"
                :value="old('contact.form.privacy_note', $form['privacy_note'] ?? '')"
                help="Shown as supporting copy; the Privacy Policy link is wired to the published privacy page."
            />
        </div>
    </details>

    <details class="admin-section-details rounded-[var(--radius-control)] border border-line bg-surface-elevated p-3" open>
        <summary class="cursor-pointer font-semibold text-ink">3. Contact details (displayed)</summary>
        <div class="mt-3 space-y-3">
            <x-input label="Heading" name="contact[details][heading]" :value="old('contact.details.heading', $details['heading'] ?? '')" />
            <x-textarea label="Introduction" name="contact[details][intro]" rows="2">{{ old('contact.details.intro', $details['intro'] ?? '') }}</x-textarea>
            <div class="grid gap-3 sm:grid-cols-2">
                <x-input label="Email" name="contact[details][email]" type="text" :value="old('contact.details.email', $details['email'] ?? '')" help="Public display address only." />
                <x-input label="Phone" name="contact[details][phone]" type="text" :value="old('contact.details.phone', $details['phone'] ?? '')" />
            </div>
            <x-input label="Location" name="contact[details][location]" :value="old('contact.details.location', $details['location'] ?? '')" />
            <x-textarea label="Placeholder note" name="contact[details][placeholder_note]" rows="2">{{ old('contact.details.placeholder_note', $details['placeholder_note'] ?? '') }}</x-textarea>

            <input type="hidden" name="contact[details][use_placeholders]" value="0">
            <label class="flex cursor-pointer items-start gap-3">
                <input
                    type="checkbox"
                    name="contact[details][use_placeholders]"
                    value="1"
                    class="mt-1 size-4 rounded border-line text-brand focus:ring-brand/30"
                    @checked($usePlaceholders)
                >
                <span class="text-sm text-ink">
                    <span class="font-medium">Example / placeholder details</span>
                    <span class="mt-0.5 block text-ink-muted">When enabled, mailto/tel links stay disabled and the placeholder note is shown.</span>
                </span>
            </label>
        </div>
    </details>

    <details class="admin-section-details rounded-[var(--radius-control)] border border-line bg-surface-elevated p-3" @if ($contactErrorOpen('contact.mail')) open @endif>
        <summary class="cursor-pointer font-semibold text-ink">4. Email notifications (delivery)</summary>
        <div class="mt-3 space-y-3">
            <p class="text-sm text-ink-muted">
                Separate from the displayed contact email. Notifications remain disabled in code until a genuine recipient
                and working mail configuration are available. Do not set a placeholder address here.
            </p>
            <x-input
                label="Notification recipient"
                name="contact[mail][notification_recipient]"
                type="email"
                :value="old('contact.mail.notification_recipient', $mail['notification_recipient'] ?? '')"
                help="Operational inbox for staff alerts — not shown on the storefront."
            />
            <input type="hidden" name="contact[mail][notifications_enabled]" value="0">
            <label class="flex cursor-pointer items-start gap-3">
                <input
                    type="checkbox"
                    name="contact[mail][notifications_enabled]"
                    value="1"
                    class="mt-1 size-4 rounded border-line text-brand focus:ring-brand/30"
                    @checked($notificationsEnabled)
                >
                <span class="text-sm text-ink">
                    <span class="font-medium">Enable email notifications</span>
                    <span class="mt-0.5 block text-ink-muted">Stored for future use; outbound mail is not sent while placeholders or incomplete mail config remain.</span>
                </span>
            </label>
        </div>
    </details>
</div>
