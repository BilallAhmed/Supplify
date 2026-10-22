import { fitProductCutoutsIn } from './fit-product-cutout';

export function initDailyPicks(root = document) {
    const section = root.querySelector('[data-daily-picks]');
    if (!section) return () => {};
    const slides = [...section.querySelectorAll('[data-pick-slide]')];
    const dots = [...section.querySelectorAll('[data-pick-dot]')];
    const status = section.querySelector('[data-pick-status]');
    const viewport = section.querySelector('[data-pick-viewport]');
    const cleanupFit = fitProductCutoutsIn(section, '[data-featured-grid-media]', 'img', { fill: .8 });
    const events = [];
    let index = 0;
    const on = (element, event, callback) => {
        if (!element) return;
        element.addEventListener(event, callback);
        events.push(() => element.removeEventListener(event, callback));
    };
    const show = next => {
        if (!slides.length) return;
        index = (next + slides.length) % slides.length;
        slides.forEach((slide, i) => {
            slide.classList.toggle('is-active', i === index);
            slide.inert = i !== index;
            slide.setAttribute('aria-hidden', String(i !== index));
        });
        dots.forEach((dot, i) => dot.setAttribute('aria-pressed', String(i === index)));
        status.textContent = `Product ${index + 1} of ${slides.length}: ${slides[index].dataset.productName}`;
    };
    on(section.querySelector('[data-pick-prev]'), 'click', () => show(index - 1));
    on(section.querySelector('[data-pick-next]'), 'click', () => show(index + 1));
    dots.forEach((dot, i) => on(dot, 'click', () => show(i)));
    on(viewport, 'keydown', event => {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        show(event.key === 'Home' ? 0 : event.key === 'End' ? slides.length - 1 : index + (event.key === 'ArrowRight' ? 1 : -1));
    });
    show(0);
    return () => { events.forEach(off => off()); cleanupFit(); };
}
