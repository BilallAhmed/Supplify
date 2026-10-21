import gsap from 'gsap';
import { prefersReducedMotion } from './motion-prefs';

/** Photograph-led transitions: keep the outgoing photograph fully covered until
 * the next decoded scene has dissolved over it. Never clip either background. */
export function initHero(root = document) {
    const hero = root.querySelector('[data-home-hero]');
    if (!hero) return () => {};
    const slides = [...hero.querySelectorAll('[data-hero-slide]')];
    if (!slides.length) return () => {};
    const dots = [...hero.querySelectorAll('[data-hero-dot]')];
    const controls = hero.querySelector('[data-hero-controls]');
    const play = hero.querySelector('[data-hero-play]');
    const header = document.querySelector('.site-header.is-over-hero');
    const live = hero.querySelector('[data-hero-live]');
    const media = window.matchMedia('(prefers-reduced-motion: reduce)');
    const cleanups = [];
    const ctx = gsap.context(() => {}, hero);
    const tracked = (fn) => ctx.add(fn);
    let index = Math.max(0, slides.findIndex(s => s.classList.contains('is-active')));
    let busy = false, pending = null, dead = false, timer = null, tl = null;
    let userPaused = false, hovered = false, focused = false, visible = true;
    let touchX = null;
    const on = (target, event, fn, options) => {
        target?.addEventListener(event, fn, options);
        cleanups.push(() => target?.removeEventListener(event, fn, options));
    };
    const parts = (slide) => [slide.querySelector('.home-hero__eyebrow'),
        ...slide.querySelectorAll('.home-hero__line'), slide.querySelector('.home-hero__body'),
        slide.querySelector('.home-hero__actions')].filter(Boolean);
    slides.forEach(slide => {
        const title = slide.querySelector('[data-hero-title]');
        if (title && !title.querySelector('.home-hero__line')) {
            const line = document.createElement('span');
            const wrap = document.createElement('span');
            line.className = 'home-hero__line'; line.textContent = title.textContent.trim();
            wrap.className = 'home-hero__line-wrap'; wrap.append(line); title.replaceChildren(wrap);
        }
    });
    const inert = (slide, value) => {
        slide.inert = value;
        slide.setAttribute('aria-hidden', String(value));
        slide.querySelectorAll('a[tabindex="-1"]').forEach(a => { if (!value) a.removeAttribute('tabindex'); });
    };
    const tone = () => {
        const light = slides[index].dataset.heroTone === 'light';
        hero.dataset.heroTone = light ? 'light' : 'dark';
        hero.classList.toggle('is-tone-light', light); hero.classList.toggle('is-tone-dark', !light);
        header?.classList.toggle('is-hero-light', light); header?.classList.toggle('is-hero-dark', !light);
        dots.forEach((dot, i) => {
            dot.classList.toggle('is-active', i === index);
            dot.setAttribute('aria-selected', String(i === index));
            dot.tabIndex = i === index ? 0 : -1;
        });
    };
    const stop = () => { clearTimeout(timer); timer = null; };
    const schedule = () => {
        stop();
        if (!dead && !busy && !userPaused && !hovered && !focused && visible && !document.hidden &&
            !media.matches && slides.length > 1) timer = setTimeout(() => go(index + 1), 7000);
    };
    const hydrate = async (slide) => {
        const img = slide.querySelector('[data-hero-scene-image]');
        if (!img) return true;
        const source = img.dataset.src;
        if (source) { img.src = source; delete img.dataset.src; }
        try { await img.decode(); } catch { return img.complete && img.naturalWidth > 0; }
        return img.naturalWidth > 0;
    };
    const settle = () => tracked(() => {
        slides.forEach((slide, i) => {
            slide.classList.toggle('is-active', i === index);
            slide.classList.remove('is-entering', 'is-leaving');
            inert(slide, i !== index);
            gsap.set(slide, {autoAlpha:i === index ? 1 : 0, zIndex:i === index ? 2 : 0});
            gsap.set(parts(slide), {clearProps:'transform,opacity,visibility,clipPath'});
            const image = slide.querySelector('[data-hero-scene-image]');
            if (image) gsap.set(image, {clearProps:'transform'});
        });
        tone();
    });
    const revealCopy = (timeline, slide, at = 0) => {
        const lines = slide.querySelectorAll('.home-hero__line');
        if (lines.length) timeline.fromTo(lines, {yPercent:105}, {
            yPercent:0, duration:0.7, ease:'power3.out', clearProps:'transform'}, at);
        const secondary = [slide.querySelector('.home-hero__eyebrow'), slide.querySelector('.home-hero__body')].filter(Boolean);
        if (secondary.length) timeline.fromTo(secondary, {opacity:0}, {
            opacity:1, duration:0.45, stagger:0.06, clearProps:'opacity'}, at + 0.08);
        // The CTA remains visible and clickable throughout the entrance.
    };
    const go = async (requested) => {
        const next = (requested + slides.length) % slides.length;
        if (busy) { pending = next; return; }
        if (next === index || dead) return;
        busy = true; stop(); tl?.kill(); settle();
        const loaded = await hydrate(slides[next]);
        if (dead) return;
        if (!loaded) { busy = false; pending = null; schedule(); return; }
        const current = slides[index], incoming = slides[next];
        const finish = () => {
            index = next; busy = false; settle();
            if (live) live.textContent = incoming.querySelector('[data-hero-title]')?.textContent || '';
            const queued = pending; pending = null;
            if (queued !== null && queued !== index) go(queued); else schedule();
            hydrate(slides[(index + 1) % slides.length]);
        };
        if (media.matches) { finish(); return; }
        tracked(() => {
            incoming.classList.add('is-entering');
            gsap.set(incoming, {autoAlpha:0, zIndex:3});
            // Current remains opaque beneath incoming: no exposed green surface.
            tl = gsap.timeline({onComplete:finish});
            tl.to(incoming, {autoAlpha:1, duration:0.65, ease:'sine.inOut'}, 0);
            const img = incoming.querySelector('[data-hero-scene-image]');
            if (img) tl.fromTo(img, {scale:1.025}, {scale:1, duration:1.1, ease:'power2.out', clearProps:'transform'}, 0);
            revealCopy(tl, incoming, 0.18);
        });
    };
    const playUi = () => {
        play?.setAttribute('aria-pressed', String(userPaused));
        play?.setAttribute('aria-label', userPaused ? 'Play automatic slideshow' : 'Pause automatic slideshow');
        const pauseIcon = play?.querySelector('.is-pause'), playIcon = play?.querySelector('.is-play');
        if (pauseIcon) pauseIcon.hidden = userPaused;
        if (playIcon) playIcon.hidden = !userPaused;
    };
    on(hero.querySelector('[data-hero-next]'), 'click', () => go((pending ?? index) + 1));
    on(hero.querySelector('[data-hero-prev]'), 'click', () => go((pending ?? index) - 1));
    dots.forEach((dot, i) => on(dot, 'click', () => go(i)));
    on(play, 'click', () => { userPaused = !userPaused; playUi(); schedule(); });
    on(hero, 'mouseenter', () => { hovered = true; stop(); });
    on(hero, 'mouseleave', () => { hovered = false; schedule(); });
    on(hero, 'focusin', () => { focused = true; stop(); });
    on(hero, 'focusout', e => { if (!hero.contains(e.relatedTarget)) { focused = false; schedule(); } });
    on(hero, 'keydown', e => {
        if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
            e.preventDefault(); go((pending ?? index) + (e.key === 'ArrowRight' ? 1 : -1));
        }
    });
    on(hero, 'touchstart', e => { touchX = e.changedTouches[0]?.clientX ?? null; }, {passive:true});
    on(hero, 'touchend', e => {
        const dx = (e.changedTouches[0]?.clientX ?? touchX) - touchX;
        if (touchX !== null && Math.abs(dx) > 48) go(index + (dx < 0 ? 1 : -1));
        touchX = null;
    }, {passive:true});
    on(document, 'visibilitychange', schedule);
    on(media, 'change', () => { tl?.progress(1); settle(); schedule(); });
    const io = new IntersectionObserver(([entry]) => { visible = entry.isIntersecting; schedule(); }, {threshold:0.15});
    io.observe(hero);
    settle(); playUi();
    tracked(() => {
        if (!prefersReducedMotion()) {
            tl = gsap.timeline();
            const img = slides[index].querySelector('[data-hero-scene-image]');
            if (img) tl.fromTo(img, {scale:1.035}, {scale:1, duration:1.2, ease:'power2.out', clearProps:'transform'}, 0);
            revealCopy(tl, slides[index], 0.12);
        }
        if (controls) gsap.set(controls, {autoAlpha:1, clearProps:'transform'});
    });
    hydrate(slides[(index + 1) % slides.length]); schedule();
    return () => { dead = true; stop(); tl?.kill(); io.disconnect(); cleanups.forEach(fn => fn()); ctx.revert(); };
}
