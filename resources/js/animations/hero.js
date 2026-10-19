import gsap from 'gsap';
import { hasFinePointer, prefersReducedMotion } from './smooth-scroll';

const AUTOPLAY_MS = 7000;
const ENTER_DURATION = 0.9;
const EXIT_DURATION = 0.7;
const BG_DURATION = 1.05;
const STAGGER = 0.08;
const TEXT_X = 28;

export function initHero(root = document) {
    const hero = root.querySelector('[data-home-hero]');
    if (! hero) return () => {};

    const slides = [...hero.querySelectorAll('[data-hero-slide]')];
    const dots = [...hero.querySelectorAll('[data-hero-dot]')];
    const progressEls = [...hero.querySelectorAll('[data-hero-progress]')];
    const prevBtn = hero.querySelector('[data-hero-prev]');
    const nextBtn = hero.querySelector('[data-hero-next]');
    const playBtn = hero.querySelector('[data-hero-play]');
    const live = hero.querySelector('[data-hero-live]');
    const bgImages = [...hero.querySelectorAll('[data-hero-bg-image]')];

    let index = Math.max(0, slides.findIndex((s) => s.classList.contains('is-active')));
    let animating = false;
    let activeTl = null;
    let autoplayPaused = false;
    let autoplayUserPaused = false;
    let progressTween = null;
    let rafId = 0;
    let lastTs = 0;
    let visible = true;
    let pageVisible = true;
    const reduced = prefersReducedMotion();
    const finePointer = hasFinePointer();
    const canParallax = ! reduced && finePointer;

    const pointer = { x: 0, y: 0, tx: 0, ty: 0 };

    const killActive = () => {
        activeTl?.kill();
        activeTl = null;
    };

    const setInert = (slideEl, inert) => {
        if (inert) slideEl.setAttribute('inert', '');
        else slideEl.removeAttribute('inert');
        slideEl.setAttribute('aria-hidden', inert ? 'true' : 'false');
        slideEl.querySelectorAll('a, button').forEach((el) => {
            if (inert) el.setAttribute('tabindex', '-1');
            else el.removeAttribute('tabindex');
        });
    };

    const parts = (slideEl) => ({
        lines: [...slideEl.querySelectorAll('[data-hero-line]')],
        products: [...slideEl.querySelectorAll('[data-hero-product]')],
        enters: [...slideEl.querySelectorAll('[data-hero-enter]')],
        parallaxes: [...slideEl.querySelectorAll('[data-hero-parallax]')],
    });

    const resetPointerState = (slideEl) => {
        parts(slideEl).parallaxes.forEach((el) => {
            el.style.transform = '';
        });
    };

    const showSettled = (slideEl) => {
        const p = parts(slideEl);
        gsap.set(slideEl, { autoAlpha: 1 });
        gsap.set(p.lines, { autoAlpha: 1, x: 0, y: 0 });
        // Products fade only — stay grounded on the surface (no y/scale hover)
        gsap.set(p.enters, { autoAlpha: 1, x: 0, y: 0, scale: 1, clearProps: 'transform' });
    };

    const setEntranceStart = (slideEl) => {
        const p = parts(slideEl);
        gsap.set(p.lines, { autoAlpha: 0, x: -TEXT_X, y: 0 });
        gsap.set(p.enters, { autoAlpha: 0, x: 0, y: 0, scale: 1 });
        p.parallaxes.forEach((el) => {
            el.style.transform = '';
        });
    };

    const playEntrance = (slideEl) => {
        const p = parts(slideEl);
        if (reduced) {
            showSettled(slideEl);
            return gsap.timeline();
        }

        const tl = gsap.timeline({ defaults: { ease: 'power2.out' } });
        tl.to(
            p.lines,
            { autoAlpha: 1, x: 0, duration: ENTER_DURATION, stagger: STAGGER },
            0
        );
        tl.to(
            p.enters,
            {
                autoAlpha: 1,
                duration: ENTER_DURATION * 0.85,
                stagger: 0.06,
                ease: 'power2.out',
            },
            0.12
        );
        return tl;
    };

    const playExit = (slideEl) => {
        const p = parts(slideEl);
        if (reduced) {
            gsap.set(slideEl, { autoAlpha: 0 });
            return gsap.timeline();
        }

        const tl = gsap.timeline({ defaults: { ease: 'power1.inOut' } });
        tl.to(p.lines, { autoAlpha: 0, x: -12, duration: EXIT_DURATION, stagger: 0.03 }, 0);
        tl.to(p.enters, { autoAlpha: 0, duration: EXIT_DURATION * 0.9, stagger: 0.04 }, 0.05);
        return tl;
    };

    const hydrateImage = (img) => {
        if (! img) return Promise.resolve();
        const src = img.getAttribute('data-src');
        if (src) {
            img.setAttribute('src', src);
            img.removeAttribute('data-src');
        }
        if (typeof img.decode === 'function') {
            return img.decode().catch(() => {});
        }
        if (img.complete) return Promise.resolve();
        return new Promise((resolve) => {
            img.addEventListener('load', resolve, { once: true });
            img.addEventListener('error', resolve, { once: true });
        });
    };

    const hydrateSlideImages = (slideEl) => {
        if (! slideEl) return Promise.resolve();
        return Promise.all(
            [...slideEl.querySelectorAll('img')].map((img) => hydrateImage(img))
        );
    };

    const hydrateBackground = (i) => hydrateImage(bgImages[i]);

    const prefetchNeighbor = (from) => {
        const next = (from + 1) % slides.length;
        hydrateSlideImages(slides[next]);
        hydrateBackground(next);
    };

    const syncDots = (next) => {
        dots.forEach((d, i) => {
            d.classList.toggle('is-active', i === next);
            d.setAttribute('aria-selected', i === next ? 'true' : 'false');
        });
        if (live) {
            live.textContent =
                slides[next].querySelector('[data-hero-title]')?.textContent?.trim() ||
                `Slide ${next + 1}`;
        }
    };

    const settleBackground = (next) => {
        bgImages.forEach((img, i) => {
            const active = i === next;
            img.classList.toggle('is-active', active);
            gsap.set(img, {
                opacity: active ? 1 : 0,
                zIndex: active ? 1 : 0,
                clearProps: active ? '' : 'zIndex',
            });
            img.style.transform = '';
        });
    };

    const crossfadeBackground = (from, to) => {
        const currentBg = bgImages[from];
        const nextBg = bgImages[to];
        if (! nextBg || from === to) {
            settleBackground(to);
            return gsap.timeline();
        }

        const tl = gsap.timeline();
        gsap.set(nextBg, { opacity: 0, zIndex: 2 });
        gsap.set(currentBg, { opacity: 1, zIndex: 1 });
        nextBg.classList.add('is-active');

        tl.to(nextBg, { opacity: 1, duration: BG_DURATION, ease: 'power1.inOut' }, 0);
        tl.to(currentBg, { opacity: 0, duration: BG_DURATION, ease: 'power1.inOut' }, 0);
        tl.add(() => {
            currentBg.classList.remove('is-active');
            gsap.set(currentBg, { zIndex: 0 });
            gsap.set(nextBg, { zIndex: 1 });
        });
        return tl;
    };

    const stopProgress = () => {
        progressTween?.kill();
        progressTween = null;
        progressEls.forEach((el) => {
            gsap.set(el, { scaleX: 0 });
        });
    };

    const startProgress = () => {
        stopProgress();
        if (reduced || autoplayPaused || autoplayUserPaused || slides.length < 2) return;
        const el = progressEls[index];
        if (! el) return;
        gsap.set(el, { scaleX: 0, transformOrigin: 'left center' });
        progressTween = gsap.to(el, {
            scaleX: 1,
            duration: AUTOPLAY_MS / 1000,
            ease: 'none',
            onComplete: () => go(1, { fromAuto: true }),
        });
    };

    const finishSlideSwap = (current, incoming, next) => {
        current.classList.remove('is-active', 'is-leaving');
        incoming.classList.remove('is-entering');
        incoming.classList.add('is-active');
        setInert(current, true);
        setInert(incoming, false);
        gsap.set(current, { autoAlpha: 0, zIndex: 0 });
        gsap.set(incoming, { autoAlpha: 1, zIndex: 2 });
        showSettled(incoming);
        settleBackground(next);
        index = next;
    };

    const setActive = (next, { fromAuto = false } = {}) => {
        if (animating || next === index || ! slides[next]) return;
        animating = true;
        killActive();
        stopProgress();

        const from = index;
        const current = slides[from];
        const incoming = slides[next];

        syncDots(next);
        prefetchNeighbor(next);
        resetPointerState(incoming);

        const startTransition = () => {
            current.classList.add('is-leaving');
            incoming.classList.add('is-entering');
            setEntranceStart(incoming);
            gsap.set(incoming, { autoAlpha: 1, zIndex: 3 });
            gsap.set(current, { zIndex: 2 });

            if (reduced) {
                finishSlideSwap(current, incoming, next);
                animating = false;
                if (! fromAuto) pauseAutoplayBriefly();
                startProgress();
                return;
            }

            activeTl = gsap.timeline({
                onComplete: () => {
                    finishSlideSwap(current, incoming, next);
                    animating = false;
                    activeTl = null;
                    if (! fromAuto) pauseAutoplayBriefly();
                    startProgress();
                },
            });

            // Overlapping crossfades — no hard mid-transition cut
            activeTl.add(crossfadeBackground(from, next), 0);
            activeTl.add(playExit(current), 0);
            activeTl.add(playEntrance(incoming), 0.22);
        };

        Promise.all([hydrateSlideImages(incoming), hydrateBackground(next)]).then(startTransition);
    };

    let resumeTimer = null;
    const pauseAutoplayBriefly = () => {
        autoplayPaused = true;
        stopProgress();
        clearTimeout(resumeTimer);
        resumeTimer = setTimeout(() => {
            if (! autoplayUserPaused && visible && pageVisible) {
                autoplayPaused = false;
                startProgress();
            }
        }, 4500);
    };

    const go = (dir, opts = {}) => {
        setActive((index + dir + slides.length) % slides.length, opts);
    };

    const updatePlayUi = () => {
        if (! playBtn) return;
        const paused = autoplayUserPaused;
        playBtn.setAttribute('aria-pressed', paused ? 'true' : 'false');
        playBtn.setAttribute(
            'aria-label',
            paused ? 'Play automatic slideshow' : 'Pause automatic slideshow'
        );
        const pauseIcon = playBtn.querySelector('.is-pause');
        const playIcon = playBtn.querySelector('.is-play');
        if (pauseIcon) pauseIcon.hidden = paused;
        if (playIcon) playIcon.hidden = ! paused;
        hero.classList.toggle('is-autoplay-paused', paused);
    };

    const setUserPaused = (paused) => {
        autoplayUserPaused = paused;
        autoplayPaused = paused;
        updatePlayUi();
        if (paused) stopProgress();
        else if (visible && pageVisible) startProgress();
    };

    prevBtn?.addEventListener('click', () => go(-1));
    nextBtn?.addEventListener('click', () => go(1));
    dots.forEach((dot, i) => {
        dot.addEventListener('click', () => setActive(i));
    });
    playBtn?.addEventListener('click', () => setUserPaused(! autoplayUserPaused));

    hero.addEventListener('mouseenter', () => {
        if (! autoplayUserPaused) {
            autoplayPaused = true;
            stopProgress();
        }
    });
    hero.addEventListener('mouseleave', () => {
        pointer.tx = 0;
        pointer.ty = 0;
        if (! autoplayUserPaused && visible && pageVisible) {
            autoplayPaused = false;
            startProgress();
        }
    });
    hero.addEventListener('focusin', () => {
        if (! autoplayUserPaused) {
            autoplayPaused = true;
            stopProgress();
        }
    });
    hero.addEventListener('focusout', (e) => {
        if (! hero.contains(e.relatedTarget) && ! autoplayUserPaused && visible && pageVisible) {
            autoplayPaused = false;
            startProgress();
        }
    });

    // Touch swipe
    let touchX = null;
    hero.addEventListener(
        'touchstart',
        (e) => {
            touchX = e.changedTouches[0]?.clientX ?? null;
        },
        { passive: true }
    );
    hero.addEventListener(
        'touchend',
        (e) => {
            if (touchX == null) return;
            const dx = (e.changedTouches[0]?.clientX ?? touchX) - touchX;
            touchX = null;
            if (Math.abs(dx) < 48) return;
            go(dx < 0 ? 1 : -1);
        },
        { passive: true }
    );

    if (canParallax) {
        hero.addEventListener('pointermove', (e) => {
            const rect = hero.getBoundingClientRect();
            const nx = ((e.clientX - rect.left) / rect.width) * 2 - 1;
            const ny = ((e.clientY - rect.top) / rect.height) * 2 - 1;
            pointer.tx = Math.max(-1, Math.min(1, nx));
            pointer.ty = Math.max(-1, Math.min(1, ny));
        });
    }

    const tick = (ts) => {
        rafId = 0;
        if (! pageVisible || ! visible || reduced || ! canParallax) return;

        const dt = lastTs ? Math.min(0.05, (ts - lastTs) / 1000) : 0.016;
        lastTs = ts;

        pointer.x += (pointer.tx - pointer.x) * Math.min(1, dt * 5.5);
        pointer.y += (pointer.ty - pointer.y) * Math.min(1, dt * 5.5);

        const active = slides[index];
        if (! active) {
            rafId = requestAnimationFrame(tick);
            return;
        }

        parts(active).products.forEach((product) => {
            const paraEl = product.querySelector('[data-hero-parallax]');
            if (! paraEl) return;
            const depth = Number(product.dataset.depth || 0.7);
            // Grounded products: ~2–4px X, ~1–2px Y — no rotate/scale/float
            const maxX = 2 + depth * 2;
            const maxY = 1 + depth * 1;
            const px = pointer.x * maxX;
            const py = pointer.y * maxY;
            if (Math.abs(px) < 0.03 && Math.abs(py) < 0.03) {
                paraEl.style.transform = '';
            } else {
                paraEl.style.transform = `translate3d(${px}px, ${py}px, 0)`;
            }
        });

        // Keep the loop alive only while the hero is on-screen.
        rafId = requestAnimationFrame(tick);
    };

    const startParallaxLoop = () => {
        if (! canParallax || rafId || ! visible || ! pageVisible || reduced) return;
        lastTs = 0;
        rafId = requestAnimationFrame(tick);
    };

    const stopParallaxLoop = () => {
        if (rafId) cancelAnimationFrame(rafId);
        rafId = 0;
        lastTs = 0;
    };

    const io = new IntersectionObserver(
        ([entry]) => {
            visible = Boolean(entry?.isIntersecting);
            if (! visible) {
                autoplayPaused = true;
                stopProgress();
                stopParallaxLoop();
            } else if (! autoplayUserPaused && pageVisible) {
                autoplayPaused = false;
                startProgress();
                startParallaxLoop();
            }
        },
        { threshold: 0.2 }
    );
    io.observe(hero);

    const onVisibility = () => {
        pageVisible = document.visibilityState === 'visible';
        if (! pageVisible) {
            stopProgress();
            stopParallaxLoop();
        } else if (visible && ! autoplayUserPaused) {
            autoplayPaused = false;
            startProgress();
            startParallaxLoop();
        }
    };
    document.addEventListener('visibilitychange', onVisibility);

    // Boot
    hydrateSlideImages(slides[index]);
    hydrateBackground(index);
    prefetchNeighbor(index);
    settleBackground(index);
    slides.forEach((s, i) => {
        gsap.set(s, i === index ? { autoAlpha: 1, zIndex: 2 } : { autoAlpha: 0, zIndex: 0 });
        setInert(s, i !== index);
        if (i === index) resetPointerState(s);
    });
    setEntranceStart(slides[index]);
    if (reduced) showSettled(slides[index]);
    else playEntrance(slides[index]);
    updatePlayUi();
    if (! reduced) startProgress();
    startParallaxLoop();

    return () => {
        clearTimeout(resumeTimer);
        stopParallaxLoop();
        killActive();
        stopProgress();
        io.disconnect();
        document.removeEventListener('visibilitychange', onVisibility);
    };
}
