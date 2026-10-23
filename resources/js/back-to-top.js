import { scrollPageToTop } from './smooth-scroll';

function initBackToTop() {
    const button = document.querySelector('[data-back-to-top]');
    if (!button || button.dataset.initialized) return;
    button.dataset.initialized = 'true';
    let queued = false;
    const update = () => {
        const visible = window.scrollY > 480;
        button.classList.toggle('is-visible', visible);
        document.documentElement.classList.toggle('back-to-top-visible', visible);
        button.inert = !visible;
        button.tabIndex = visible ? 0 : -1;
        queued = false;
    };
    window.addEventListener('scroll', () => {
        if (!queued) { queued = true; requestAnimationFrame(update); }
    }, { passive: true });
    window.addEventListener('pageshow', update);
    button.addEventListener('click', () => {
        const main = document.getElementById('main-content');
        if (main) {
            if (!main.hasAttribute('tabindex')) main.setAttribute('tabindex', '-1');
            main.focus({ preventScroll: true });
        }
        scrollPageToTop();
    });
    update();
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initBackToTop, { once: true });
else initBackToTop();
