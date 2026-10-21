<section class="home-campaign" aria-label="The routine edit" data-routine-campaign>
    <x-container>
        <div class="home-campaign__top"><p class="editorial-kicker">The routine edit</p><div class="home-campaign__tabs" role="tablist" aria-label="Explore supplement collections"><button type="button" role="tab" id="routine-tab-daily" aria-controls="routine-panel-daily" aria-selected="true" data-campaign-tab="0">Daily essentials</button><button type="button" role="tab" id="routine-tab-minerals" aria-controls="routine-panel-minerals" aria-selected="false" tabindex="-1" data-campaign-tab="1">Mineral essentials</button></div></div>
        <div class="home-campaign__stage">
            <article class="home-campaign__panel is-active" id="routine-panel-daily" role="tabpanel" aria-labelledby="routine-tab-daily" data-campaign-panel>
                <img src="{{ asset('images/homepage/hero/campaign-daily-vitamins-desktop.webp') }}" alt="Supplify vitamins arranged on natural stone display stands" width="1920" height="900" loading="lazy" decoding="async" data-campaign-image>
                <div class="home-campaign__copy"><p>Small rituals. Everyday care.</p><h2>A little care.<br>Every day.</h2><p>Find vitamins and essentials that fit your routine.</p><a class="home-view-all home-campaign__cta" href="{{ route('shop.index') }}">Explore daily essentials <svg width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></a></div>
            </article>
            <article class="home-campaign__panel" id="routine-panel-minerals" role="tabpanel" aria-labelledby="routine-tab-minerals" data-campaign-panel aria-hidden="true" inert>
                <img src="{{ asset('images/homepage/hero/campaign-minerals-desktop.webp') }}" alt="Supplify Magnesium and Calcium Citrate on matching stone display stands" width="1920" height="900" loading="lazy" decoding="async" data-campaign-image>
                <div class="home-campaign__copy"><p>Make space for the essentials.</p><h2>Your routine.<br>Your rhythm.</h2><p>Explore our mineral formulas, with clear labels at a glance.</p><a class="home-view-all home-campaign__cta" href="{{ route('shop.index', ['q'=>'magnesium']) }}">Explore mineral essentials <svg width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></a></div>
            </article>
            <button class="home-campaign__pause" type="button" data-campaign-pause aria-pressed="false" aria-label="Pause campaign animation"><svg class="home-campaign__pause-icon" width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><path d="M5 3v10M11 3v10" stroke="currentColor" stroke-width="1.8"/></svg><svg class="home-campaign__play-icon" width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><path d="m5 3 8 5-8 5Z" fill="currentColor"/></svg><span data-campaign-pause-label>Pause</span></button>
        </div>
    </x-container>
</section>
