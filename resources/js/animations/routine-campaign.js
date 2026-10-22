import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { fitProductCutoutsIn } from './fit-product-cutout';

gsap.registerPlugin(ScrollTrigger);

/** Independent product hover and reversible, scroll-linked wellness detail motion. */
export function initRoutineCampaign(root = document) {
    const section = root.querySelector('[data-routine-campaign]');
    if (!section) return () => {};
    const destroyFit = fitProductCutoutsIn(section, '[data-banner-product]', 'img', { fill: .94 });
    // Keep the entrance consumed for this document, including bfcache restores.
    const entrance = section.querySelector('[data-banner-entrance]');
    const entranceContext = gsap.context(() => {
        if (!entrance || section.dataset.productsRevealed === 'true') return;
        if (matchMedia('(prefers-reduced-motion: reduce)').matches) {
            section.dataset.productsRevealed = 'true';
            return;
        }
        gsap.fromTo(entrance, { x: -48, autoAlpha: 0 }, {
            x: 0, autoAlpha: 1, duration: .95, ease: 'power3.out',
            onStart: () => { section.dataset.productsRevealed = 'true'; },
            scrollTrigger: { trigger: section, start: 'top 88%', once: true, toggleActions: 'play none none none' },
        });
    }, section);
    const media = gsap.matchMedia();
    media.add('(min-width: 1024px) and (prefers-reduced-motion: no-preference)', () => {
        gsap.fromTo(section.querySelector('[data-banner-scroll]'), { x: 24 }, {
            x: () => -Math.min(72, section.querySelector('.wellness-banner--daily').clientWidth * .075),
            ease: 'none',
            scrollTrigger: {
                trigger: section,
                start: 'top 90%',
                end: 'bottom 20%',
                scrub: .22,
                invalidateOnRefresh: true,
            },
        });
    });
    return () => { media.revert(); entranceContext.revert(); destroyFit(); };
}
