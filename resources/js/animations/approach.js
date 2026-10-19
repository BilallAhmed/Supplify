import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { prefersReducedMotion } from './smooth-scroll';

gsap.registerPlugin(ScrollTrigger);

const BALANCE_MQ = '(min-width: 1024px)';

/**
 * Match the left column height to the full timeline (including pre-animation cards)
 * so the image frame absorbs leftover space and the CTA aligns with the last card.
 */
function initColumnBalance(section, intro, timeline) {
    if (! intro || ! timeline) {
        return () => {};
    }

    const mq = window.matchMedia(BALANCE_MQ);
    let frame = 0;
    let ro = null;

    const clear = () => {
        section.style.removeProperty('--approach-balance-h');
        section.classList.remove('is-balanced');
    };

    const measure = () => {
        if (! mq.matches) {
            clear();
            return;
        }

        // Use stage offsets (not the stretched list box) so opacity/transform
        // animations never change the balance height.
        const stages = [...timeline.querySelectorAll('[data-approach-stage]')];
        const last = stages.at(-1);

        if (! last) {
            clear();
            return;
        }

        const height = Math.round(last.offsetTop + last.offsetHeight);

        if (height < 1) {
            clear();
            return;
        }

        const next = `${height}px`;
        if (section.style.getPropertyValue('--approach-balance-h') !== next) {
            section.style.setProperty('--approach-balance-h', next);
            section.classList.add('is-balanced');
            // Defer refresh so we don't hitch mid-scroll on layout measure.
            requestAnimationFrame(() => ScrollTrigger.refresh());
        } else {
            section.classList.add('is-balanced');
        }
    };

    const schedule = () => {
        cancelAnimationFrame(frame);
        frame = requestAnimationFrame(() => {
            frame = requestAnimationFrame(measure);
        });
    };

    const onMqChange = () => schedule();

    mq.addEventListener('change', onMqChange);
    window.addEventListener('resize', schedule);

    if (typeof ResizeObserver !== 'undefined') {
        ro = new ResizeObserver(schedule);
        ro.observe(timeline);
        [...timeline.querySelectorAll('[data-approach-stage]')].forEach((stage) => ro.observe(stage));
    }

    const img = intro.querySelector('.home-approach__visual-img');
    if (img && ! img.complete) {
        img.addEventListener('load', schedule, { once: true });
    }

    if (document.fonts?.ready) {
        document.fonts.ready.then(schedule).catch(() => {});
    }

    schedule();

    return () => {
        cancelAnimationFrame(frame);
        mq.removeEventListener('change', onMqChange);
        window.removeEventListener('resize', schedule);
        ro?.disconnect();
        clear();
    };
}

/**
 * Scroll-linked journey timeline for the Our Approach section.
 * Content remains fully visible without JS; reduced-motion shows the complete static journey.
 */
export function initApproach(root = document) {
    const section = root.querySelector('[data-home-approach]');
    if (! section) return () => {};

    const intro = section.querySelector('[data-approach-intro]');
    const timeline = section.querySelector('[data-approach-timeline]');
    const stages = [...section.querySelectorAll('[data-approach-stage]')];
    const path = section.querySelector('[data-approach-path]');
    const reduced = prefersReducedMotion();

    section.classList.add('is-ready');

    const destroyBalance = initColumnBalance(section, intro, timeline);

    if (reduced || stages.length === 0) {
        section.classList.add('is-static');
        return () => {
            destroyBalance();
            section.classList.remove('is-ready', 'is-static');
        };
    }

    const ctx = gsap.context(() => {
        // Opacity/visibility only — keep layout height stable for column balance.
        gsap.set(intro, { autoAlpha: 0, y: 28 });
        gsap.set(stages, { autoAlpha: 0, y: 32 });

        if (path && typeof path.getTotalLength === 'function') {
            const pathLength = path.getTotalLength() || 100;
            gsap.set(path, { strokeDasharray: pathLength, strokeDashoffset: pathLength });
        }

        const stageAt = (i) => 0.18 + i * 0.16;

        const tl = gsap.timeline({
            defaults: { ease: 'power2.out' },
            scrollTrigger: {
                trigger: section,
                start: 'top 72%',
                end: 'bottom 55%',
                scrub: 0.45,
                invalidateOnRefresh: true,
                onUpdate(self) {
                    const p = self.progress;
                    stages.forEach((stage, i) => {
                        const active = p >= stageAt(i) - 0.02;
                        if (stage.classList.contains('is-active') !== active) {
                            stage.classList.toggle('is-active', active);
                        }
                    });
                },
                onLeaveBack() {
                    stages.forEach((stage) => stage.classList.remove('is-active'));
                },
            },
        });

        // Intro includes the About CTA — reveal together so the action stays available.
        tl.to(intro, { autoAlpha: 1, y: 0, duration: 0.35 }, 0);

        if (path) {
            tl.to(path, { strokeDashoffset: 0, duration: 0.85, ease: 'none' }, 0.12);
        }

        tl.to(stages, { autoAlpha: 1, y: 0, duration: 0.28, stagger: 0.16 }, 0.18);
    }, section);

    return () => {
        destroyBalance();
        ctx.revert();
        section.classList.remove('is-ready', 'is-static');
        stages.forEach((stage) => stage.classList.remove('is-active'));
    };
}
