import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
gsap.registerPlugin(ScrollTrigger);

/** A shallow perspective settle; no translation or rotation of a flat label. */
export function initProductDepth(root = document) {
    const section = root.querySelector('[data-product-depth]');
    if (!section) return () => {};
    const stage = section.querySelector('[data-depth-stage]');
    const bottle = section.querySelector('[data-depth-bottle]');
    const shadow = section.querySelector('[data-depth-shadow]');
    const accent = section.querySelector('[data-depth-accent]');
    const mm = gsap.matchMedia();
    mm.add({desktop:'(min-width:768px)', mobile:'(max-width:767px)', reduced:'(prefers-reduced-motion:reduce)'}, ctx => {
        section.classList.add('is-ready');
        if (ctx.conditions.reduced || !bottle) {
            section.classList.add('is-static');
            return () => section.classList.remove('is-ready', 'is-static');
        }
        const tl = gsap.timeline({scrollTrigger:{trigger:section, start:'top 85%', end:'center 48%', scrub:0.35}});
        tl.fromTo(bottle, {scale:0.96, rotationY:ctx.conditions.mobile ? 0 : -5, transformOrigin:'50% 100%'},
            {scale:1, rotationY:0, duration:1, ease:'none'}, 0);
        if (shadow) tl.fromTo(shadow, {scaleX:0.9, opacity:0.4}, {scaleX:1, opacity:0.55, duration:1, ease:'none'}, 0);
        if (accent) tl.fromTo(accent, {scale:0.92}, {scale:1, duration:1, ease:'none'}, 0);
        // Product details remain visible and their button usable at every scroll position.
        const refresh = () => ScrollTrigger.refresh();
        if (!bottle.complete) bottle.addEventListener('load', refresh, {once:true});
        return () => { bottle.removeEventListener('load', refresh); section.classList.remove('is-ready', 'is-static'); };
    });
    return () => mm.revert();
}
