import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { prefersReducedMotion, hasFinePointer } from './motion-prefs';

gsap.registerPlugin(ScrollTrigger);

export function initInteractions(root = document) {
    const cleanups = [];
    const reduced = prefersReducedMotion();

    // Section reveals — varied directions, not only Y fade
    if (! reduced) {
        root.querySelectorAll('[data-reveal]').forEach((el) => {
            const type = el.getAttribute('data-reveal') || 'up';
            const from = {
                up: { y: 40, autoAlpha: 0 },
                left: { x: -48, autoAlpha: 0 },
                right: { x: 48, autoAlpha: 0 },
                scale: { scale: 0.94, autoAlpha: 0 },
                clip: { clipPath: 'inset(0 0 100% 0)', autoAlpha: 1 },
            }[type] || { y: 40, autoAlpha: 0 };

            const anim = gsap.from(el, {
                ...from,
                duration: 0.9,
                ease: 'power2.out',
                scrollTrigger: {
                    trigger: el,
                    start: 'top 85%',
                    once: true,
                },
            });
            cleanups.push(() => anim.scrollTrigger?.kill());
        });
    }

    // Magnetic CTAs
    if (hasFinePointer() && ! reduced) {
        root.querySelectorAll('[data-magnetic]').forEach((btn) => {
            const strength = Number(btn.getAttribute('data-magnetic') || 18);
            const onMove = (e) => {
                const r = btn.getBoundingClientRect();
                const x = e.clientX - (r.left + r.width / 2);
                const y = e.clientY - (r.top + r.height / 2);
                gsap.to(btn, { x: x / strength, y: y / strength, duration: 0.35, ease: 'power2.out' });
            };
            const onLeave = () => gsap.to(btn, { x: 0, y: 0, duration: 0.5, ease: 'power3.out' });
            btn.addEventListener('pointermove', onMove);
            btn.addEventListener('pointerleave', onLeave);
            cleanups.push(() => {
                btn.removeEventListener('pointermove', onMove);
                btn.removeEventListener('pointerleave', onLeave);
            });
        });
    }

    return () => cleanups.forEach((fn) => fn());
}
