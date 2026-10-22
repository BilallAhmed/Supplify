let activeLenis = null;

export function scrollPageToTop() {
    if (activeLenis) {
        activeLenis.scrollTo(0, { duration: 0.85 });
        return;
    }
    window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth' });
}

async function initSmoothScrollTrial() {
    // Admin pages never opt in. The URL switch gives a no-build A/B comparison.
    if (document.body.dataset.smoothScroll !== 'true') return;
    const desktop = window.matchMedia('(min-width: 1024px) and (hover: hover) and (pointer: fine)');
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    let dispose = null;
    let generation = 0;

    const sync = async () => {
        const current = ++generation;
        dispose?.();
        dispose = null;
        if (!desktop.matches || reduced.matches) return;
        try {
            const [{ default: Lenis }, { default: gsap }, { ScrollTrigger }] = await Promise.all([
                import('lenis'), import('gsap'), import('gsap/ScrollTrigger'),
            ]);
            if (current !== generation || !desktop.matches || reduced.matches) return;
            gsap.registerPlugin(ScrollTrigger);
            const lenis = new Lenis({
                autoRaf: false,
                lerp: 0.12,
                smoothWheel: true,
                wheelMultiplier: 1,
                syncTouch: false,
                allowNestedScroll: true,
                anchors: { offset: -88 },
                stopInertiaOnNavigate: true,
                prevent: (node) => node.matches?.('[role="dialog"], [data-lenis-prevent], input, textarea, select'),
            });
            activeLenis = lenis;
            const tick = (time) => lenis.raf(time * 1000);
            const onScroll = () => ScrollTrigger.update();
            lenis.on('scroll', onScroll);
            gsap.ticker.add(tick);

            // Existing Quick View owns the page lock; Lenis follows it.
            let locked = false;
            const syncLock = () => {
                const next = document.documentElement.classList.contains('qv-open') || document.body.classList.contains('overflow-hidden');
                if (next === locked) return;
                locked = next;
                if (locked) lenis.stop();
                else lenis.start();
            };
            const observer = new MutationObserver(syncLock);
            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
            observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
            syncLock();
            dispose = () => {
                observer.disconnect();
                gsap.ticker.remove(tick);
                lenis.off('scroll', onScroll);
                lenis.destroy();
                if (activeLenis === lenis) activeLenis = null;
            };
        } catch {
            // Native scrolling remains available if the optional trial cannot load.
            activeLenis = null;
        }
    };
    desktop.addEventListener('change', sync);
    reduced.addEventListener('change', sync);
    window.addEventListener('pageshow', sync);
    window.addEventListener('pagehide', () => { ++generation; dispose?.(); dispose = null; });
    sync();
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initSmoothScrollTrial, { once: true });
else initSmoothScrollTrial();
