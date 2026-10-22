export function initWhyChoose(root = document) {
    const section = root.querySelector('[data-why-supplify]');
    if (!section) return () => {};
    const button = section.querySelector('[data-strip-pause]');
    const label = section.querySelector('[data-strip-label]');
    const toggle = () => {
        const paused = section.classList.toggle('is-paused');
        button.setAttribute('aria-pressed', String(paused));
        button.setAttribute('aria-label', paused ? 'Play moving strip' : 'Pause moving strip');
        label.textContent = paused ? 'Play' : 'Pause';
    };
    button.addEventListener('click', toggle);
    let inside = false;
    const sync = () => section.classList.toggle('is-in-view', inside && !document.hidden);
    const observer = new IntersectionObserver(([entry]) => { inside = entry.isIntersecting; sync(); });
    observer.observe(section);
    document.addEventListener('visibilitychange', sync);
    return () => { observer.disconnect(); button.removeEventListener('click', toggle); document.removeEventListener('visibilitychange', sync); section.classList.remove('is-in-view'); };
}
