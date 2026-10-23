@extends('layouts.storefront')

@php
    /** @var \App\Models\Page $page */
    /** @var array<string, mixed> $contact */
    /** @var array<string, string> $enquiryTypes */
    /** @var bool $allowsDirectLinks */
    $banner = $contact['banner'] ?? [];
    $form = $contact['form'] ?? [];
    $details = $contact['details'] ?? [];
    $seoTitle = $page->seo_title ?: ($banner['heading'] ?? $page->title);
    $seoDescription = $page->seo_description
        ?: ($banner['intro'] ?? \Illuminate\Support\Str::limit(strip_tags((string) $page->body), 160));
    $detailEmail = trim((string) ($details['email'] ?? ''));
    $detailPhone = trim((string) ($details['phone'] ?? ''));
    $detailLocation = trim((string) ($details['location'] ?? ''));
    $usePlaceholders = filter_var($details['use_placeholders'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $bannerImage = \App\Support\ContactPageContent::resolveBannerImage($banner['image'] ?? null);
    $imagePath = $banner['image'] ?? \App\Support\ContactPageContent::HERO_IMAGE;
    $absolute = $imagePath ? public_path(ltrim(str_replace('\\', '/', (string) $imagePath), '/')) : null;
    $size = ($absolute && is_file($absolute)) ? @getimagesize($absolute) : false;
    $imgW = is_array($size) ? (int) $size[0] : 1905;
    $imgH = is_array($size) ? (int) $size[1] : 440;
@endphp

@section('title', $seoTitle.' — '.config('app.name'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) $seoDescription), 160))
@section('canonical', route('pages.show', $page->slug))
@section('og_title', $seoTitle)
@section('og_description', \Illuminate\Support\Str::limit(strip_tags((string) $seoDescription), 160))

@push('head')
    @if ($bannerImage['is_photography'] && $bannerImage['src'])
        <meta property="og:image" content="{{ $bannerImage['src'] }}">
        <meta name="twitter:image" content="{{ $bannerImage['src'] }}">
    @endif
@endpush

@section('content')
    <div class="contact-page" data-contact-page>
        <section class="page-banner contact-banner" aria-labelledby="contact-banner-heading">
            <div class="page-banner__frame contact-banner__frame">
                @if ($bannerImage['is_photography'] && $bannerImage['src'])
                    <img
                        src="{{ $bannerImage['src'] }}"
                        alt="{{ $banner['image_alt'] ?? '' }}"
                        class="page-banner__photo contact-banner__photo"
                        width="{{ $imgW }}"
                        height="{{ $imgH }}"
                        fetchpriority="high"
                        decoding="async"
                    >
                @else
                    <div class="page-banner__photo page-banner__photo--fallback contact-banner__tone" aria-hidden="true"></div>
                @endif
                <div class="page-banner__shade contact-banner__shade" aria-hidden="true"></div>

                <x-container class="page-banner__body contact-banner__inner">
                    <div class="page-banner__copy contact-banner__copy">
                        <nav class="about-hero__crumb" aria-label="Breadcrumb">
                            <ol class="about-hero__crumb-list">
                                <li><a href="{{ route('home') }}">Home</a></li>
                                <li aria-hidden="true">/</li>
                                <li aria-current="page">Contact</li>
                            </ol>
                        </nav>
                        @if (! empty($banner['eyebrow']))
                            <p class="about-hero__eyebrow">{{ $banner['eyebrow'] }}</p>
                        @endif
                        <h1 id="contact-banner-heading" class="about-hero__title">
                            {{ $banner['heading'] ?? 'Let’s get in touch' }}
                        </h1>
                        @if (! empty($banner['intro']))
                            <p class="about-hero__intro">{{ $banner['intro'] }}</p>
                        @endif
                    </div>
                </x-container>
            </div>
        </section>

        <section class="contact-main" aria-labelledby="contact-form-heading">
            <x-container class="contact-main__inner">
                <div class="contact-layout">
                    <div class="contact-form-panel">
                        <header class="contact-panel__head">
                            <h2 id="contact-form-heading" class="contact-panel__title">
                                {{ $form['heading'] ?? 'Send us a message' }}
                            </h2>
                            @if (! empty($form['intro']))
                                <p class="contact-panel__intro">{{ $form['intro'] }}</p>
                            @endif
                        </header>

                        @if ($errors->any() && ! $errors->has('website'))
                            <x-alert variant="danger" class="mb-5" role="alert">
                                Please check the highlighted fields and try again.
                            </x-alert>
                        @endif

                        <form
                            method="POST"
                            action="{{ route('contact.store') }}"
                            class="contact-form"
                            novalidate
                            x-data="{ submitting: false }"
                            @submit="if (submitting) { $event.preventDefault(); return; } submitting = true"
                        >
                            @csrf

                            {{-- Discreet honeypot — leave empty --}}
                            <div class="contact-honeypot" aria-hidden="true" inert>
                                <label for="contact-website">Website</label>
                                <input
                                    id="contact-website"
                                    type="text"
                                    name="website"
                                    value=""
                                    tabindex="-1"
                                    autocomplete="off"
                                >
                            </div>

                            <div class="contact-form__grid">
                                <div>
                                    <x-input
                                        id="contact-name"
                                        name="name"
                                        type="text"
                                        label="Full name"
                                        autocomplete="name"
                                        required
                                        aria-required="true"
                                        :value="old('name')"
                                    />
                                </div>

                                <div>
                                    <x-input
                                        id="contact-email"
                                        name="email"
                                        type="email"
                                        label="Email address"
                                        autocomplete="email"
                                        inputmode="email"
                                        required
                                        aria-required="true"
                                        :value="old('email')"
                                    />
                                </div>

                                <div>
                                    <x-input
                                        id="contact-phone"
                                        name="phone"
                                        type="tel"
                                        label="Phone number (optional)"
                                        autocomplete="tel"
                                        inputmode="tel"
                                        :value="old('phone')"
                                    />
                                </div>

                                <div>
                                    <x-select
                                        id="contact-enquiry-type"
                                        name="enquiry_type"
                                        label="Enquiry type"
                                        required
                                        aria-required="true"
                                    >
                                        <option value="" disabled @selected(old('enquiry_type') === null || old('enquiry_type') === '')>Select an enquiry type</option>
                                        @foreach ($enquiryTypes as $value => $label)
                                            <option value="{{ $value }}" @selected(old('enquiry_type') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </x-select>
                                </div>

                                <div class="contact-form__full">
                                    <x-textarea
                                        id="contact-message"
                                        name="message"
                                        label="Message"
                                        rows="6"
                                        required
                                        aria-required="true"
                                        maxlength="5000"
                                    >{{ old('message') }}</x-textarea>
                                </div>
                            </div>

                            <div class="contact-form__actions">
                                <x-button
                                    type="submit"
                                    variant="primary"
                                    size="lg"
                                    ::disabled="submitting"
                                    ::aria-busy="submitting.toString()"
                                >
                                    <span x-show="!submitting">Send message</span>
                                    <span x-cloak x-show="submitting">Sending…</span>
                                </x-button>
                            </div>
                        </form>

                        <p class="contact-privacy">
                            We’ll use your details to respond to your enquiry. Read our
                            <a href="{{ route('pages.show', 'privacy') }}">Privacy Policy</a>.
                        </p>
                    </div>

                    <aside class="contact-details-panel" aria-labelledby="contact-details-heading">
                        <header class="contact-panel__head">
                            <h2 id="contact-details-heading" class="contact-panel__title">
                                {{ $details['heading'] ?? 'Contact details' }}
                            </h2>
                            @if (! empty($details['intro']))
                                <p class="contact-panel__intro">{{ $details['intro'] }}</p>
                            @endif
                        </header>

                        <dl class="contact-details">
                            @if ($detailEmail !== '')
                                <div class="contact-details__row">
                                    <dt>Email</dt>
                                    <dd>
                                        @if ($allowsDirectLinks)
                                            <a href="mailto:{{ $detailEmail }}">{{ $detailEmail }}</a>
                                        @else
                                            <span>{{ $detailEmail }}</span>
                                        @endif
                                    </dd>
                                </div>
                            @endif

                            @if ($detailPhone !== '')
                                <div class="contact-details__row">
                                    <dt>Phone</dt>
                                    <dd>
                                        @if ($allowsDirectLinks)
                                            <a href="tel:{{ preg_replace('/[^\d+]/', '', $detailPhone) }}">{{ $detailPhone }}</a>
                                        @else
                                            <span>{{ $detailPhone }}</span>
                                        @endif
                                    </dd>
                                </div>
                            @endif

                            @if ($detailLocation !== '')
                                <div class="contact-details__row">
                                    <dt>Location</dt>
                                    <dd><span>{{ $detailLocation }}</span></dd>
                                </div>
                            @endif
                        </dl>

                        @if ($usePlaceholders && ! empty($details['placeholder_note']))
                            <p class="contact-details__note" role="note">{{ $details['placeholder_note'] }}</p>
                        @endif
                    </aside>
                </div>
            </x-container>
        </section>
    </div>
@endsection
