import { fitProductCutoutsIn } from './fit-product-cutout';

/** Compact editorial grid: normalize bottle size without pinning page scroll. */
export function initFeatured(root = document) {
    const section = root.querySelector('[data-home-featured-grid]');
    if (!section) return () => {};
    return fitProductCutoutsIn(section, '[data-featured-grid-media]', 'img', { fill: .8 });
}
