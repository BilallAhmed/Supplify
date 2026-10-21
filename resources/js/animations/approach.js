import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { prefersReducedMotion } from './motion-prefs';

gsap.registerPlugin(ScrollTrigger);

const BALANCE_MQ = '(min-width: 1024px)';

/**
 * Match the left column height to the full timeline so the image frame
 * absorbs leftover space and the CTA aligns with the last card.
 */
function initColumnBalance(section, intro, timeline) {
    if (!intro || !timeline) {
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
        if (!mq.matches) {
            clear();
            return;
        }

        const stages = [...timeline.querySelectorAll('[data-approach-stage]')];
        const last = stages.at(-1);
        if (!last) {
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
    if (img && !img.complete) {
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
 * Scroll-linked journey for Our Approach.
 * Path + active step share one scrubbed progress so jumps and pin refreshes stay in sync.
 */
export function initApproach(root = document) {
    const section = root.querySelector('[data-home-approach]');
    if (!section) return () => {};

    const intro = section.querySelector('[data-approach-intro]');
    const timeline = section.querySelector('[data-approach-timeline]');
    const stages = [...section.querySelectorAll('[data-approach-stage]')];
    const path = section.querySelector('[data-approach-path]');
    const image = section.querySelector('.home-approach__visual-img');
    const reduced = prefersReducedMotion();

    section.classList.add('is-ready');

    const destroyBalance = initColumnBalance(section, intro, timeline);

    if (reduced || stages.length === 0 || !timeline) {
        section.classList.add('is-static');
        return () => {
            destroyBalance();
            section.classList.remove('is-ready', 'is-static');
        };
    }

    const ctx = gsap.context(() => {
        let active = -1;
        const activate = (index) => {
            const next = Math.max(0, Math.min(stages.length - 1, index));
            if (active === next) return;
            active = next;
            stages.forEach((stage, i) => {
                stage.classList.toggle('is-active', i === next);
                stage.classList.toggle('is-seen', i < next);
            });
        };
        activate(0);

        const length = path && typeof path.getTotalLength === 'function'
            ? (path.getTotalLength() || 100)
            : 100;

        if (path) {
            gsap.set(path, { strokeDasharray: length, strokeDashoffset: length });
        }

        const applyProgress = (progress) => {
            if (path) {
                gsap.set(path, { strokeDashoffset: length * (1 - progress) });
            }
            if (progress <= 0.02) {
                activate(0);
                return;
            }
            if (progress >= 0.98) {
                activate(stages.length - 1);
                return;
            }
            activate(Math.floor(progress * stages.length));
        };

        ScrollTrigger.create({
            // Scrub across the section itself so #approach landings start at step 01,
            // then path + nodes advance as the taller timeline scrolls through.
            trigger: section,
            start: 'top 20%',
            end: 'bottom 55%',
            scrub: 0.4,
            invalidateOnRefresh: true,
            onUpdate(self) {
                applyProgress(self.progress);
            },
            onRefresh(self) {
                applyProgress(self.progress);
            },
            onLeaveBack() {
                applyProgress(0);
            },
        });

        if (image) {
            gsap.fromTo(
                image,
                { scale: 1.04 },
                {
                    scale: 1,
                    ease: 'none',
                    scrollTrigger: {
                        trigger: section,
                        start: 'top 70%',
                        end: 'bottom 35%',
                        scrub: 0.45,
                        invalidateOnRefresh: true,
                    },
                },
            );
        }

        // Featured pin spacing shifts document height after first paint — refresh until stable.
        const refresh = () => ScrollTrigger.refresh();
        requestAnimationFrame(() => requestAnimationFrame(refresh));
        [300, 800, 1600].forEach((ms) => window.setTimeout(refresh, ms));
        window.addEventListener('load', refresh, { once: true });
    }, section);

    return () => {
        destroyBalance();
        ctx.revert();
        section.classList.remove('is-ready', 'is-static');
        stages.forEach((stage) => stage.classList.remove('is-active', 'is-seen'));
    };
}
