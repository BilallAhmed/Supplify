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

/** Each stage follows its real position instead of a compressed section range. */
export function initApproach(root = document) {
    const section = root.querySelector('[data-home-approach]');
    if (!section) return () => {};
    const timeline = section.querySelector('[data-approach-timeline]');
    const stages = [...section.querySelectorAll('[data-approach-stage]')];
    const path = section.querySelector('[data-approach-path]');
    section.classList.add('is-ready');
    const destroyBalance = initColumnBalance(section, section.querySelector('[data-approach-intro]'), timeline);
    if (prefersReducedMotion() || !stages.length) {
        section.classList.add('is-static');
        return () => { destroyBalance(); section.classList.remove('is-ready', 'is-static'); };
    }
    const ctx = gsap.context(() => {
        const activate = index => {
            const next = Math.max(0, Math.min(stages.length - 1, index));
            stages.forEach((stage, i) => {
                stage.classList.toggle('is-active', i === next);
                stage.classList.toggle('is-seen', i < next);
                if (i === next) stage.setAttribute('aria-current', 'step');
                else stage.removeAttribute('aria-current');
            });
        };
        const sync = () => {
            let active = 0;
            stages.forEach((stage, i) => { if (stage.getBoundingClientRect().top <= innerHeight * .35) active = i; });
            activate(active);
        };
        stages.forEach((stage, i) => ScrollTrigger.create({
            trigger: stage, start: 'top 35%', end: 'bottom 35%',
            onEnter: () => activate(i), onEnterBack: () => activate(i),
            onLeaveBack: () => activate(i - 1), onRefresh: sync,
        }));
        if (path) {
            const length = path.getTotalLength?.() || 100;
            gsap.fromTo(path, { strokeDasharray: length, strokeDashoffset: length }, {
                strokeDashoffset: 0, ease: 'none',
                scrollTrigger: { trigger: stages[0], endTrigger: stages.at(-1), start: 'top 35%', end: 'top 35%', scrub: .35, invalidateOnRefresh: true },
            });
        }
        sync();
    }, section);
    return () => {
        destroyBalance(); ctx.revert(); section.classList.remove('is-ready', 'is-static');
        stages.forEach(stage => { stage.classList.remove('is-active', 'is-seen'); stage.removeAttribute('aria-current'); });
    };
}
