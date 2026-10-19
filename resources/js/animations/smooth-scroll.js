import Lenis from 'lenis';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

export function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

export function hasFinePointer() {
    return window.matchMedia('(hover: hover) and (pointer: fine)').matches;
}

/**
 * Lenis + ScrollTrigger bridge for the homepage only.
 * Uses lerp-only smoothing (no duration) so continuous wheel/trackpad
 * input stays responsive instead of stacking eased animations.
 *
 * @returns {{ lenis: import('lenis').default | null, destroy: () => void }}
 */
export function initSmoothScroll() {
    if (prefersReducedMotion()) {
        document.documentElement.classList.add('motion-reduced');
        return { lenis: null, destroy() {} };
    }

    const lenis = new Lenis({
        // Lerp-only: duration-based easing feels laggy under continuous input.
        lerp: 0.12,
        smoothWheel: true,
        wheelMultiplier: 1,
        touchMultiplier: 1.4,
        syncTouch: false,
        allowNestedScroll: true,
    });

    lenis.on('scroll', ScrollTrigger.update);

    // Keep Alpine `siteHeader.scrolled` in sync — class binding owns `is-scrolled`
    // (Lenis may not update window.scrollY the same way as native scroll).
    let headerRaf = 0;
    let pendingScroll = 0;
    lenis.on('scroll', ({ scroll }) => {
        pendingScroll = scroll;
        if (headerRaf) return;
        headerRaf = requestAnimationFrame(() => {
            headerRaf = 0;
            const header = document.querySelector('.site-header');
            if (! header) return;
            const alpine = header._x_dataStack?.[0];
            const y = pendingScroll;
            const next = alpine?.scrolled ? y > 12 : y > 20;
            if (alpine) {
                if (alpine.scrolled !== next) alpine.scrolled = next;
            } else {
                header.classList.toggle('is-scrolled', next);
            }
        });
    });

    const ticker = (time) => {
        lenis.raf(time * 1000);
    };
    gsap.ticker.add(ticker);
    // Keep default lag smoothing so dropped frames ease back in instead of jumping.

    document.documentElement.classList.add('lenis-active');

    return {
        lenis,
        destroy() {
            if (headerRaf) cancelAnimationFrame(headerRaf);
            gsap.ticker.remove(ticker);
            lenis.destroy();
            document.documentElement.classList.remove('lenis-active');
        },
    };
}
