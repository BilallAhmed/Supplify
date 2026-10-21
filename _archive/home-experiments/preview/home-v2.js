import 'lenis/dist/lenis.css';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { initSmoothScroll, prefersReducedMotion } from './animations/smooth-scroll';

gsap.registerPlugin(ScrollTrigger);

document.documentElement.classList.add('js-enabled');

function revealOnScroll(root) {
    const nodes = root.querySelectorAll('[data-v2-reveal]');
    if (! nodes.length) {
        return () => {};
    }

    if (prefersReducedMotion()) {
        nodes.forEach((node) => node.classList.add('is-visible'));
        return () => {};
    }

    const triggers = [];
    nodes.forEach((node) => {
        gsap.set(node, { autoAlpha: 0, y: 28 });
        const tween = gsap.to(node, {
            autoAlpha: 1,
            y: 0,
            duration: 0.85,
            ease: 'power2.out',
            scrollTrigger: {
                trigger: node,
                start: 'top 88%',
                toggleActions: 'play none none none',
            },
        });
        triggers.push(tween.scrollTrigger);
    });

    return () => triggers.forEach((t) => t?.kill());
}

function initHero(root) {
    const hero = root.querySelector('[data-v2-hero]');
    if (! hero || prefersReducedMotion()) {
        hero?.querySelectorAll('[data-v2-reveal]').forEach((n) => n.classList.add('is-visible'));
        return () => {};
    }

    const reveals = hero.querySelectorAll('[data-v2-reveal]');
    const img = hero.querySelector('[data-v2-parallax]');

    gsap.set(reveals, { autoAlpha: 0, y: 36 });
    const intro = gsap.timeline({ defaults: { ease: 'power2.out' } });
    intro.to(reveals, {
        autoAlpha: 1,
        y: 0,
        duration: 0.9,
        stagger: 0.12,
        delay: 0.15,
    });

    let parallax;
    if (img) {
        parallax = gsap.to(img, {
            yPercent: 12,
            ease: 'none',
            scrollTrigger: {
                trigger: hero,
                start: 'top top',
                end: 'bottom top',
                scrub: true,
            },
        });
    }

    return () => {
        intro.kill();
        parallax?.scrollTrigger?.kill();
        parallax?.kill();
    };
}

function initChapters(root) {
    const section = root.querySelector('[data-v2-chapters]');
    if (! section) {
        return () => {};
    }

    const pin = section.querySelector('.home-v2-chapters__pin');
    const scenes = [...section.querySelectorAll('[data-v2-scene]')];
    const panels = [...section.querySelectorAll('[data-v2-panel]')];
    const ticks = [...section.querySelectorAll('[data-v2-tick]')];
    const count = Math.max(scenes.length, 1);

    const setActive = (index) => {
        scenes.forEach((el, i) => el.classList.toggle('is-active', i === index));
        panels.forEach((el, i) => {
            const on = i === index;
            el.classList.toggle('is-active', on);
            el.setAttribute('aria-hidden', on ? 'false' : 'true');
        });
        ticks.forEach((el, i) => el.classList.toggle('is-active', i === index));
    };

    if (prefersReducedMotion() || ! pin || count < 2) {
        setActive(0);
        return () => {};
    }

    const trigger = ScrollTrigger.create({
        trigger: section,
        start: 'top top',
        end: () => `+=${window.innerHeight * count * 0.85}`,
        pin: pin,
        scrub: 0.55,
        anticipatePin: 1,
        onUpdate: (self) => {
            const index = Math.min(count - 1, Math.floor(self.progress * count));
            setActive(index);
        },
    });

    return () => trigger.kill();
}

function boot() {
    const root = document.querySelector('[data-home-v2]');
    if (! root) {
        return;
    }

    const { destroy: destroyScroll } = initSmoothScroll();
    const destroyHero = initHero(root);
    const destroyChapters = initChapters(root);
    const destroyReveals = revealOnScroll(root);

    window.addEventListener(
        'pagehide',
        () => {
            destroyHero?.();
            destroyChapters?.();
            destroyReveals?.();
            destroyScroll?.();
        },
        { once: true },
    );
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
