/**
 * Homepage concept — Phase 2 cinematic scroll
 * Native scrolling + GSAP ScrollTrigger. No Lenis / wheel hijacking.
 * No Three.js — depth via transform, shadow, and background transitions.
 */
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { prefersReducedMotion } from './animations/smooth-scroll';

gsap.registerPlugin(ScrollTrigger);

document.documentElement.classList.add('js-enabled');

const DESKTOP_MQ = '(min-width: 900px)';

function killContext(ctx) {
    ctx?.revert();
}

function initConcept(root) {
    if (! root) {
        return () => {};
    }

    if (prefersReducedMotion()) {
        root.classList.add('hc--static');
        document.documentElement.classList.add('motion-reduced');
        return () => {
            root.classList.remove('hc--static');
        };
    }

    root.classList.add('hc--motion');

    const mm = gsap.matchMedia();
    const cleanups = [];

    mm.add(DESKTOP_MQ, () => {
        const destroy = buildDesktop(root);
        return destroy;
    });

    mm.add(`(max-width: 899px)`, () => {
        const destroy = buildMobile(root);
        return destroy;
    });

    cleanups.push(() => mm.revert());

    return () => {
        cleanups.forEach((fn) => fn());
        root.classList.remove('hc--motion');
        ScrollTrigger.getAll()
            .filter((st) => root.contains(st.trigger) || root.contains(st.pin || null))
            .forEach((st) => st.kill());
    };
}

function buildDesktop(root) {
    const ctx = gsap.context(() => {
        const pin = root.querySelector('[data-hc-pin]');
        const journey = root.querySelector('[data-hc-journey]');
        const bottle = root.querySelector('[data-hc-bottle]');
        const bottleSlot = root.querySelector('[data-hc-bottle-slot]');
        const wash = root.querySelector('[data-hc-wash]');
        const heroCopy = root.querySelector('[data-hc-hero-copy]');
        const introCopy = root.querySelector('[data-hc-intro-copy]');
        const heroLines = root.querySelectorAll('[data-hc-hero-line]');
        const introLines = root.querySelectorAll('[data-hc-intro-line]');

        // —— Hero entrance ——
        if (heroLines.length) {
            gsap.set(heroLines, { autoAlpha: 0, y: 28 });
        }
        if (bottle) {
            gsap.set(bottle, { autoAlpha: 0, y: 48, scale: 0.92, rotate: -2 });
        }
        if (introCopy) {
            gsap.set(introCopy, { autoAlpha: 0 });
        }
        if (introLines.length) {
            gsap.set(introLines, { autoAlpha: 0, y: 20 });
        }

        const introTl = gsap.timeline({ defaults: { ease: 'power2.out' } });
        if (bottle) {
            introTl.to(bottle, {
                autoAlpha: 1,
                y: 0,
                scale: 1,
                rotate: 0,
                duration: 1.05,
                delay: 0.08,
            }, 0);
        }
        if (heroLines.length) {
            introTl.to(heroLines, {
                autoAlpha: 1,
                y: 0,
                duration: 0.8,
                stagger: 0.1,
            }, 0.18);
        }

        // —— Pin: hero → intro ——
        if (journey && pin && bottle && bottleSlot) {
            const scrubTl = gsap.timeline({
                scrollTrigger: {
                    trigger: journey,
                    start: 'top top',
                    end: '+=115%',
                    pin: pin,
                    scrub: 0.65,
                    anticipatePin: 1,
                    invalidateOnRefresh: true,
                },
            });

            scrubTl
                .to(heroCopy, { autoAlpha: 0, y: -36, duration: 0.35, ease: 'none' }, 0)
                .to(wash, { autoAlpha: 0.55, scale: 1.06, duration: 0.5, ease: 'none' }, 0)
                .to(bottleSlot, {
                    xPercent: -28,
                    yPercent: 4,
                    scale: 0.78,
                    duration: 0.55,
                    ease: 'none',
                }, 0.05)
                .to(bottle, {
                    rotate: 3,
                    filter: 'drop-shadow(0 22px 36px rgba(5,70,72,0.28))',
                    duration: 0.55,
                    ease: 'none',
                }, 0.05)
                .to(pin, {
                    backgroundColor: '#ffffff',
                    duration: 0.4,
                    ease: 'none',
                }, 0.15)
                .to(introCopy, { autoAlpha: 1, duration: 0.25, ease: 'none' }, 0.35)
                .to(introLines, {
                    autoAlpha: 1,
                    y: 0,
                    duration: 0.3,
                    stagger: 0.05,
                    ease: 'none',
                }, 0.4);
        }

        // —— Range ——
        const rangeHead = root.querySelector('[data-hc-range-head]');
        const rangeCards = root.querySelectorAll('[data-hc-range-card]');
        const rangeBottles = root.querySelectorAll('[data-hc-range-bottle]');

        if (rangeHead) {
            gsap.from(rangeHead.children, {
                autoAlpha: 0,
                y: 24,
                duration: 0.7,
                stagger: 0.08,
                ease: 'power2.out',
                scrollTrigger: {
                    trigger: rangeHead,
                    start: 'top 82%',
                    toggleActions: 'play none none none',
                },
            });
        }

        rangeCards.forEach((card, i) => {
            gsap.from(card, {
                autoAlpha: 0,
                y: 56,
                scale: 0.96,
                duration: 0.75,
                ease: 'power2.out',
                scrollTrigger: {
                    trigger: card,
                    start: 'top 88%',
                    toggleActions: 'play none none none',
                },
                delay: i * 0.04,
            });
        });

        rangeBottles.forEach((img) => {
            gsap.from(img, {
                y: 28,
                scale: 0.9,
                duration: 0.85,
                ease: 'power2.out',
                scrollTrigger: {
                    trigger: img,
                    start: 'top 90%',
                    toggleActions: 'play none none none',
                },
            });
        });

        // —— Detail ——
        const detail = root.querySelector('[data-hc-detail]');
        const detailBottle = root.querySelector('[data-hc-detail-bottle]');
        const detailCopy = root.querySelector('[data-hc-detail-copy]');
        const facts = root.querySelectorAll('[data-hc-fact]');

        if (detail && detailBottle) {
            gsap.from(detailBottle, {
                autoAlpha: 0,
                scale: 0.88,
                x: -40,
                rotate: -3,
                duration: 0.9,
                ease: 'power2.out',
                scrollTrigger: {
                    trigger: detail,
                    start: 'top 70%',
                    toggleActions: 'play none none none',
                },
            });
        }

        if (detailCopy) {
            gsap.from(detailCopy.querySelectorAll('.hc-kicker, .hc-display, .hc-body'), {
                autoAlpha: 0,
                y: 22,
                duration: 0.65,
                stagger: 0.08,
                ease: 'power2.out',
                scrollTrigger: {
                    trigger: detailCopy,
                    start: 'top 78%',
                    toggleActions: 'play none none none',
                },
            });
        }

        facts.forEach((row, i) => {
            gsap.from(row, {
                autoAlpha: 0,
                y: 16,
                duration: 0.55,
                ease: 'power2.out',
                delay: i * 0.05,
                scrollTrigger: {
                    trigger: row,
                    start: 'top 90%',
                    toggleActions: 'play none none none',
                },
            });
        });

        // —— Everyday ——
        const everyday = root.querySelector('[data-hc-everyday]');
        const everydayMedia = root.querySelector('[data-hc-everyday-media] img');
        const everydayCopy = root.querySelector('[data-hc-everyday-copy]');

        if (everyday && everydayMedia) {
            gsap.fromTo(
                everydayMedia,
                { scale: 1.08, yPercent: -4 },
                {
                    scale: 1,
                    yPercent: 4,
                    ease: 'none',
                    scrollTrigger: {
                        trigger: everyday,
                        start: 'top bottom',
                        end: 'bottom top',
                        scrub: true,
                    },
                },
            );
        }

        if (everydayCopy) {
            gsap.from(everydayCopy.children, {
                autoAlpha: 0,
                y: 28,
                duration: 0.75,
                stagger: 0.09,
                ease: 'power2.out',
                scrollTrigger: {
                    trigger: everydayCopy,
                    start: 'top 80%',
                    toggleActions: 'play none none none',
                },
            });
        }

        // —— Close ——
        const closeInner = root.querySelector('[data-hc-close-inner]');
        if (closeInner) {
            gsap.from(closeInner.children, {
                autoAlpha: 0,
                y: 24,
                duration: 0.7,
                stagger: 0.08,
                ease: 'power2.out',
                scrollTrigger: {
                    trigger: closeInner,
                    start: 'top 85%',
                    toggleActions: 'play none none none',
                },
            });
        }
    }, root);

    return () => killContext(ctx);
}

function buildMobile(root) {
    const ctx = gsap.context(() => {
        const bottle = root.querySelector('[data-hc-bottle]');
        const heroLines = root.querySelectorAll('[data-hc-hero-line]');
        const introCopy = root.querySelector('[data-hc-intro-copy]');
        const introLines = root.querySelectorAll('[data-hc-intro-line]');
        const pin = root.querySelector('[data-hc-pin]');
        const journey = root.querySelector('[data-hc-journey]');

        gsap.set(heroLines, { autoAlpha: 0, y: 18 });
        if (bottle) {
            gsap.set(bottle, { autoAlpha: 0, y: 28, scale: 0.96 });
        }
        gsap.set(introLines, { autoAlpha: 0, y: 16 });

        const enter = gsap.timeline({ defaults: { ease: 'power2.out' } });
        if (bottle) {
            enter.to(bottle, { autoAlpha: 1, y: 0, scale: 1, duration: 0.75 }, 0);
        }
        enter.to(heroLines, { autoAlpha: 1, y: 0, duration: 0.55, stagger: 0.07 }, 0.1);

        // Short pin on mobile — less rotation, stacked copy swap
        if (journey && pin) {
            gsap.timeline({
                scrollTrigger: {
                    trigger: journey,
                    start: 'top top',
                    end: '+=70%',
                    pin: pin,
                    scrub: 0.5,
                    anticipatePin: 1,
                    invalidateOnRefresh: true,
                },
            })
                .to(root.querySelector('[data-hc-hero-copy]'), { autoAlpha: 0, y: -20, duration: 0.3 }, 0)
                .to(bottle, { scale: 0.86, y: -12, duration: 0.4 }, 0)
                .to(introCopy, { autoAlpha: 1, duration: 0.25 }, 0.28)
                .to(introLines, { autoAlpha: 1, y: 0, duration: 0.28, stagger: 0.04 }, 0.32);
        } else if (introCopy) {
            gsap.set(introCopy, { autoAlpha: 1 });
            gsap.set(introLines, { autoAlpha: 1, y: 0 });
        }

        root.querySelectorAll('[data-hc-range-card]').forEach((card) => {
            gsap.from(card, {
                autoAlpha: 0,
                y: 32,
                duration: 0.55,
                ease: 'power2.out',
                scrollTrigger: {
                    trigger: card,
                    start: 'top 92%',
                    toggleActions: 'play none none none',
                },
            });
        });

        const detailBottle = root.querySelector('[data-hc-detail-bottle]');
        if (detailBottle) {
            gsap.from(detailBottle, {
                autoAlpha: 0,
                y: 24,
                scale: 0.94,
                duration: 0.65,
                ease: 'power2.out',
                scrollTrigger: {
                    trigger: detailBottle,
                    start: 'top 88%',
                    toggleActions: 'play none none none',
                },
            });
        }

        root.querySelectorAll('[data-hc-fact]').forEach((row) => {
            gsap.from(row, {
                autoAlpha: 0,
                y: 12,
                duration: 0.45,
                scrollTrigger: {
                    trigger: row,
                    start: 'top 94%',
                    toggleActions: 'play none none none',
                },
            });
        });

        const everydayCopy = root.querySelector('[data-hc-everyday-copy]');
        if (everydayCopy) {
            gsap.from(everydayCopy.children, {
                autoAlpha: 0,
                y: 18,
                duration: 0.55,
                stagger: 0.06,
                scrollTrigger: {
                    trigger: everydayCopy,
                    start: 'top 88%',
                    toggleActions: 'play none none none',
                },
            });
        }

        const closeInner = root.querySelector('[data-hc-close-inner]');
        if (closeInner) {
            gsap.from(closeInner.children, {
                autoAlpha: 0,
                y: 16,
                duration: 0.55,
                stagger: 0.06,
                scrollTrigger: {
                    trigger: closeInner,
                    start: 'top 90%',
                    toggleActions: 'play none none none',
                },
            });
        }
    }, root);

    return () => killContext(ctx);
}

function boot() {
    const root = document.querySelector('[data-home-concept]');
    if (! root) {
        return;
    }

    const destroy = initConcept(root);

    const onPageHide = () => destroy?.();
    window.addEventListener('pagehide', onPageHide, { once: true });

    // Hot-nav / Livewire-ish safety
    document.addEventListener(
        'livewire:navigating',
        () => {
            destroy?.();
            window.removeEventListener('pagehide', onPageHide);
        },
        { once: true },
    );
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
