import 'lenis/dist/lenis.css';
import { initSmoothScroll } from './animations/smooth-scroll';
import { initHero } from './animations/hero';
import { initApproach } from './animations/approach';
import { initFeatured } from './animations/featured-formulas';
import { initInteractions } from './animations/interactions';

document.documentElement.classList.add('js-enabled');

function boot() {
    const { lenis, destroy: destroyScroll } = initSmoothScroll();
    const destroyHero = initHero();
    const destroyApproach = initApproach();
    const destroyFeatured = initFeatured(document, { lenis });
    const destroyInteractions = initInteractions();

    window.addEventListener(
        'pagehide',
        () => {
            destroyHero?.();
            destroyApproach?.();
            destroyFeatured?.();
            destroyInteractions?.();
            destroyScroll?.();
        },
        { once: true }
    );
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
