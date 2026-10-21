import '../css/home-balance.css';
import { prefersReducedMotion } from './animations/motion-prefs';
import { initHero } from './animations/hero';
import { initApproach } from './animations/approach';
import { initFeatured } from './animations/featured-formulas';
import { initHomePolish } from './animations/home-polish';
import { initProductHover } from './animations/product-hover';
import { initRoutineCampaign } from './animations/routine-campaign';

document.documentElement.classList.add('js-enabled');

let teardown = null;
function boot() {
    if (teardown) return;
    if (prefersReducedMotion()) {
        document.documentElement.classList.add('motion-reduced');
    }

    const destroyHero = initHero();
    // Featured pins first so later scroll sections measure against the final document height.
    const destroyFeatured = initFeatured();
    const destroyCampaign = initRoutineCampaign();
    const destroyApproach = initApproach();
    const destroyPolish = initHomePolish();
    const destroyProductHover = initProductHover();

    teardown = () => {
        destroyProductHover?.(); destroyCampaign?.(); destroyHero?.(); destroyApproach?.(); destroyFeatured?.(); destroyPolish?.();
        teardown = null;
    };

}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

window.addEventListener('pagehide', () => teardown?.());
window.addEventListener('pageshow', event => { if (event.persisted) boot(); });
