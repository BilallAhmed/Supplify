import gsap from 'gsap';
import { hasFinePointer, prefersReducedMotion } from './motion-prefs';

const DWELL_MS = 8000;

export function initHero(root = document) {
    const hero = root.querySelector('[data-home-hero]');
    const slides = [...(hero?.querySelectorAll('[data-hero-slide]') || [])];
    if (!slides.length) return () => {};
    const dots = [...hero.querySelectorAll('[data-hero-dot]')];
    const play = hero.querySelector('[data-hero-play]');
    const live = hero.querySelector('[data-hero-live]');
    const mobile = matchMedia('(max-width: 599px)');
    const reduce = matchMedia('(prefers-reduced-motion: reduce)');
    const cleanups = [];
    const motions = new Map();
    let index = 0, pending = null, busy = true, ready = false, dead = false;
    let paused = false, hovered = false, focused = false, visible = true;
    let timer, transition, touch;
    const reduced = () => reduce.matches || prefersReducedMotion();
    const on = (node, type, fn, options) => {
        node?.addEventListener(type, fn, options);
        cleanups.push(() => node?.removeEventListener(type, fn, options));
    };
    const copy = slide => [...slide.querySelectorAll('[data-hero-eyebrow], [data-hero-phrase], [data-hero-body], [data-hero-actions]')];
    const plate = slide => slide.querySelector('[data-hero-scene-plate]');
    const stop = () => clearTimeout(timer);
    const allowed = () => !paused && !hovered && !focused && visible && !document.hidden && !reduced();
    const preload = async slide => {
        const img = slide.querySelector('[data-hero-scene-image]');
        if (!img) return true;
        const src = mobile.matches ? (img.dataset.mobileSrc || img.dataset.desktopSrc) : img.dataset.desktopSrc;
        if (src && img.getAttribute('src') !== src) img.src = src;
        try { await img.decode(); } catch { /* Leave the previous slide visible on failure. */ }
        return img.complete && img.naturalWidth > 0;
    };
    const inert = (slide, value) => {
        slide.inert = value;
        slide.setAttribute('aria-hidden', String(value));
        slide.querySelectorAll('a').forEach(a => value ? a.setAttribute('tabindex', '-1') : a.removeAttribute('tabindex'));
    };
    const sync = () => {
        slides.forEach((slide, i) => {
            const active = i === index;
            slide.classList.toggle('is-active', active);
            slide.classList.remove('is-entering', 'is-leaving');
            inert(slide, !active);
            if (!active) gsap.set(slide, { autoAlpha: 0, zIndex: 0 });
        });
        dots.forEach((dot, i) => {
            dot.classList.toggle('is-active', i === index);
            dot.setAttribute('aria-selected', String(i === index));
            dot.tabIndex = i === index ? 0 : -1;
        });
        hero.dataset.heroTone = 'dark';
        hero.classList.add('is-tone-dark');
        hero.classList.remove('is-tone-light');
        const header = document.querySelector('.site-header.is-over-hero');
        header?.classList.add('is-hero-dark');
        header?.classList.remove('is-hero-light');
        if (live) live.textContent = slides[index].querySelector('[data-hero-title]')?.textContent.replace(/\s+/g, ' ').trim() || '';
    };
    const ambient = slide => {
        const target = plate(slide);
        if (target) gsap.set(target, { scale: 1 });
    };
    const schedule = () => {
        stop();
        motions.forEach((motion, slide) => motion.paused(slide !== slides[index] || !allowed()));
        if (ready && !busy && allowed() && slides.length > 1) timer = setTimeout(() => go(index + 1), DWELL_MS);
    };
    const finish = next => {
        if (dead) return;
        index = next;
        busy = false;
        ready = true;
        gsap.set(slides[index], { autoAlpha: 1, zIndex: 2 });
        // Do not clear the incoming transforms here: that caused the old end-of-transition snap.
        sync();
        ambient(slides[index]);
        preload(slides[(index + 1) % slides.length]);
        const queued = pending;
        pending = null;
        if (queued !== null && queued !== index) go(queued);
        else schedule();
    };
    const entrance = (tl, slide, at = 0) => {
        const parts = copy(slide);
        gsap.set(parts, { autoAlpha: 0, y: 25 });
        tl.to(parts, { autoAlpha: 1, y: 0, duration: .7, stagger: .09, ease: 'power3.out' }, at + .12);
        const target = plate(slide);
        if (target) {
            gsap.set(target, { scale: 1 });
        }
    };
    const go = async requested => {
        const next = (requested % slides.length + slides.length) % slides.length;
        if (dead) return;
        if (busy) { pending = next; return; }
        if (next === index) return;
        busy = true;
        stop();
        // Keep the current photograph in place until the complete incoming image is decoded.
        const loaded = await preload(slides[next]);
        if (dead) return;
        if (!loaded) { busy = false; pending = null; schedule(); return; }
        motions.get(slides[index])?.pause();
        motions.get(slides[next])?.kill();
        if (reduced()) { finish(next); return; }
        const incoming = slides[next];
        incoming.classList.add('is-entering');
        gsap.set(incoming, { autoAlpha: 0, zIndex: 3 });
        transition = gsap.timeline({ onComplete: () => finish(next) });
        transition.to(copy(slides[index]), { autoAlpha: 0, duration: .25, ease: 'power2.out' }, 0);
        transition.to(incoming, { autoAlpha: 1, duration: .85, ease: 'sine.inOut' }, 0);
        entrance(transition, incoming, .05);
    };
    on(hero.querySelector('[data-hero-next]'), 'click', () => go((pending ?? index) + 1));
    on(hero.querySelector('[data-hero-prev]'), 'click', () => go((pending ?? index) - 1));
    dots.forEach((dot, i) => on(dot, 'click', () => go(i)));
    on(play, 'click', () => {
        paused = !paused;
        play.setAttribute('aria-pressed', String(paused));
        play.setAttribute('aria-label', paused ? 'Play automatic slideshow' : 'Pause automatic slideshow');
        play.querySelector('.is-pause').hidden = paused;
        play.querySelector('.is-play').hidden = !paused;
        schedule();
    });
    if (hasFinePointer()) {
        on(hero, 'mouseenter', () => { hovered = true; schedule(); });
        on(hero, 'mouseleave', () => { hovered = false; schedule(); });
    }
    on(hero, 'focusin', () => { focused = true; schedule(); });
    on(hero, 'focusout', event => { if (!hero.contains(event.relatedTarget)) { focused = false; schedule(); } });
    on(hero, 'keydown', event => {
        if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
        event.preventDefault();
        go((pending ?? index) + (event.key === 'ArrowRight' ? 1 : -1));
    });
    on(hero, 'touchstart', e => { const p = e.changedTouches[0]; touch = p ? [p.clientX, p.clientY] : null; }, { passive: true });
    on(hero, 'touchend', e => {
        const p = e.changedTouches[0];
        if (!p || !touch) return;
        const dx = p.clientX - touch[0], dy = p.clientY - touch[1];
        touch = null;
        if (Math.abs(dx) > 56 && Math.abs(dx) > Math.abs(dy) * 1.25) go(index + (dx < 0 ? 1 : -1));
    }, { passive: true });
    on(document, 'visibilitychange', () => { transition?.paused(document.hidden); schedule(); });
    on(reduce, 'change', () => {
        if (busy && transition) transition.progress(1);
        motions.forEach(m => m.kill());
        motions.clear();
        if (reduced()) slides.forEach(s => { if (plate(s)) gsap.set(plate(s), { scale: 1 }); });
        else if (ready) ambient(slides[index]);
        schedule();
    });
    on(mobile, 'change', () => { preload(slides[index]); });
    const observer = new IntersectionObserver(([entry]) => { visible = entry.isIntersecting; schedule(); }, { threshold: .15 });
    observer.observe(hero);
    slides.forEach((slide, i) => gsap.set(slide, { autoAlpha: i === index ? 1 : 0, zIndex: i === index ? 2 : 0 }));
    sync();
    if (!reduced()) {
        slides.forEach(slide => {
            gsap.set(copy(slide), { autoAlpha: 0, y: 25 });
            if (plate(slide)) gsap.set(plate(slide), { scale: 1 });
        });
    }
    (async () => {
        await preload(slides[index]);
        if (dead) return;
        if (reduced()) finish(index);
        else {
            transition = gsap.timeline({ onComplete: () => finish(index) });
            entrance(transition, slides[index]);
        }
    })();
    return () => {
        dead = true;
        stop();
        transition?.kill();
        motions.forEach(m => m.kill());
        observer.disconnect();
        cleanups.forEach(fn => fn());
    };
}

