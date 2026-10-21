import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { prefersReducedMotion } from './animations/motion-prefs';

gsap.registerPlugin(ScrollTrigger);

document.documentElement.classList.add('js-enabled');

function initAboutJourney(root = document) {
    const section = root.querySelector('[data-about-journey]');
    if (! section) {
        return () => {};
    }

    const page = root.querySelector('[data-about-page]') || document.documentElement;
    const desktopLayout = section.querySelector('[data-about-journey-desktop]');
    const mobileLayout = section.querySelector('[data-about-journey-mobile]');
    const stagesWrap = section.querySelector('[data-about-journey-stages]');
    const stages = [...(desktopLayout?.querySelectorAll('[data-about-journey-stage]') ?? [])];
    const frames = [...(desktopLayout?.querySelectorAll('[data-about-journey-frame]') ?? [])];
    const progress = section.querySelector('[data-about-journey-progress]');
    const reduced = prefersReducedMotion();
    const desktopMq = window.matchMedia('(min-width: 1024px)');
    const shortViewportMq = window.matchMedia('(max-height: 640px)');

    let triggers = [];
    let activeIndex = -1;

    const canUseStickyJourney = () => desktopMq.matches && ! reduced && ! shortViewportMq.matches;

    const syncLayouts = () => {
        const useDesktop = canUseStickyJourney();
        page.classList.toggle('is-reduced-motion', reduced);
        page.classList.toggle('is-journey-flow', ! useDesktop && desktopMq.matches && ! reduced);

        if (desktopLayout) {
            desktopLayout.hidden = ! useDesktop;
            desktopLayout.setAttribute('aria-hidden', useDesktop ? 'false' : 'true');
            desktopLayout.toggleAttribute('inert', ! useDesktop);
        }
        if (mobileLayout) {
            mobileLayout.hidden = useDesktop;
            mobileLayout.setAttribute('aria-hidden', useDesktop ? 'true' : 'false');
            mobileLayout.toggleAttribute('inert', useDesktop);
        }
    };

    const clearTriggers = () => {
        triggers.forEach((t) => t.kill());
        triggers = [];
        activeIndex = -1;
    };

    const setActive = (index) => {
        if (index === activeIndex) {
            return;
        }
        activeIndex = index;

        stages.forEach((stage, i) => {
            const active = i === index;
            stage.classList.toggle('is-active', active);
            if (i <= index) {
                stage.classList.add('is-seen');
            }
        });

        frames.forEach((frame, i) => {
            const active = i === index;
            frame.classList.toggle('is-active', active);
            frame.setAttribute('aria-hidden', active ? 'false' : 'true');
        });

        if (progress) {
            const pct = stages.length <= 1 ? 100 : (index / (stages.length - 1)) * 100;
            progress.style.width = `${pct}%`;
        }
    };

    const activateStatic = () => {
        clearTriggers();
        stages.forEach((stage) => stage.classList.add('is-active', 'is-seen'));
        frames.forEach((frame, i) => {
            frame.classList.toggle('is-active', i === 0);
            frame.setAttribute('aria-hidden', i === 0 ? 'false' : 'true');
        });
        if (progress) {
            progress.style.width = '100%';
        }
    };

    const bindDesktopJourney = () => {
        clearTriggers();
        if (stages.length === 0) {
            return;
        }

        setActive(0);

        stages.forEach((stage, index) => {
            const isLast = index === stages.length - 1;
            triggers.push(
                ScrollTrigger.create({
                    trigger: stage,
                    start: 'top 58%',
                    // Keep the final stage active while its copy remains in the reading zone.
                    end: isLast ? 'bottom top' : 'bottom 45%',
                    onEnter: () => setActive(index),
                    onEnterBack: () => setActive(index),
                }),
            );
        });
    };

    const applyMode = () => {
        syncLayouts();

        if (! canUseStickyJourney()) {
            activateStatic();
            ScrollTrigger.refresh();
            return;
        }

        bindDesktopJourney();
        ScrollTrigger.refresh();
    };

    applyMode();

    const onChange = () => applyMode();
    const onResize = () => {
        syncLayouts();
        ScrollTrigger.refresh();
    };

    desktopMq.addEventListener?.('change', onChange);
    shortViewportMq.addEventListener?.('change', onChange);
    window.addEventListener('resize', onResize);

    return () => {
        clearTriggers();
        page.classList.remove('is-reduced-motion', 'is-journey-flow');
        desktopMq.removeEventListener?.('change', onChange);
        shortViewportMq.removeEventListener?.('change', onChange);
        window.removeEventListener('resize', onResize);
    };
}

function boot() {
    const destroyJourney = initAboutJourney();

    window.addEventListener(
        'pagehide',
        () => {
            destroyJourney?.();
        },
        { once: true },
    );
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
