import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { prefersReducedMotion, hasFinePointer } from './smooth-scroll';
import { fitProductCutoutsIn } from './fit-product-cutout';

gsap.registerPlugin(ScrollTrigger);

/**
 * Featured formulas horizontal rail.
 *
 * Desktop (fine pointer): remap vertical wheel to horizontal product travel while
 * the rail can move. At either boundary, forward the wheel to Lenis/window so the
 * page continues without trapping, jitter, or an extra click.
 *
 * Touch / coarse pointer: native pan-x + pan-y only — no wheel interception.
 *
 * @param {ParentNode} [root]
 * @param {{ lenis?: import('lenis').default | null }} [options]
 */
export function initFeatured(root = document, options = {}) {
    const section = root.querySelector('[data-home-featured]');
    if (! section) return () => {};

    const viewport = section.querySelector('[data-featured-viewport]');
    const track = section.querySelector('[data-featured-track]');
    const cards = [...section.querySelectorAll('[data-featured-card]')];
    const prevBtn = section.querySelector('[data-featured-prev]');
    const nextBtn = section.querySelector('[data-featured-next]');
    const lenis = options.lenis ?? null;

    if (! viewport || ! track || cards.length === 0) return () => {};

    const cleanups = [];
    const reduced = prefersReducedMotion();
    const finePointer = hasFinePointer();

    cleanups.push(
        fitProductCutoutsIn(section, '[data-featured-media]', '.home-featured__img', { fill: 0.7 })
    );

    const step = () => {
        const card = cards[0];
        const styles = getComputedStyle(track);
        const gap = Number.parseFloat(styles.columnGap || styles.gap || '20') || 20;
        return (card?.getBoundingClientRect().width || 320) + gap;
    };

    const maxScroll = () => Math.max(0, track.scrollWidth - viewport.clientWidth);

    // Track padding / residual offset can leave scrollLeft short of exact 0 / max.
    const edgeTolerance = () => Math.max(24, Math.round(step() * 0.08));

    let syncQueued = false;
    const syncUi = () => {
        if (syncQueued) return;
        syncQueued = true;
        requestAnimationFrame(() => {
            syncQueued = false;
            const max = maxScroll();
            const left = viewport.scrollLeft;
            const edge = edgeTolerance();
            const atStart = left <= edge;
            const atEnd = left >= max - edge;

            if (prevBtn) prevBtn.disabled = atStart || max <= 0;
            if (nextBtn) nextBtn.disabled = atEnd || max <= 0;

            const center = left + viewport.clientWidth / 2;
            cards.forEach((card) => {
                const mid = card.offsetLeft + card.offsetWidth / 2;
                const active = Math.abs(mid - center) < card.offsetWidth * 0.55;
                if (card.classList.contains('is-active') !== active) {
                    card.classList.toggle('is-active', active);
                }
            });
        });
    };

    const goPrev = () => {
        const next = Math.max(0, viewport.scrollLeft - step());
        targetScroll = next;
        viewport.scrollTo({ left: next, behavior: reduced ? 'auto' : 'smooth' });
    };
    const goNext = () => {
        const next = Math.min(maxScroll(), viewport.scrollLeft + step());
        targetScroll = next;
        viewport.scrollTo({ left: next, behavior: reduced ? 'auto' : 'smooth' });
    };

    prevBtn?.addEventListener('click', goPrev);
    nextBtn?.addEventListener('click', goNext);
    viewport.addEventListener('scroll', syncUi, { passive: true });
    window.addEventListener('resize', syncUi);

    /**
     * Forward vertical intent to the page. Overflow-x carousels often fail to
     * chain vertical wheel to the document (especially upward), which traps scroll.
     * Use Lenis lerp-only (no duration) so successive wheel ticks accumulate
     * cleanly instead of stacking competing ease animations.
     */
    const scrollPageBy = (deltaY) => {
        if (! deltaY) return;

        if (lenis && typeof lenis.scrollTo === 'function') {
            const current = typeof lenis.scroll === 'number' ? lenis.scroll : window.scrollY;
            lenis.scrollTo(current + deltaY, {
                immediate: false,
                force: true,
                programmatic: true,
                lerp: 0.14,
            });
            return;
        }

        window.scrollBy({ top: deltaY, left: 0, behavior: 'auto' });
    };

    // Latch: once released at a boundary in a direction, keep releasing until
    // the user reverses into the rail again. Prevents edge jitter/trapping.
    let releaseLatch = null; // 'start' | 'end' | null
    let targetScroll = viewport.scrollLeft;
    let railRaf = 0;

    const clampScroll = (value) => Math.min(maxScroll(), Math.max(0, value));

    const tickRail = () => {
        railRaf = 0;
        const current = viewport.scrollLeft;
        const delta = targetScroll - current;

        if (Math.abs(delta) < 0.4) {
            viewport.scrollLeft = targetScroll;
            viewport.classList.remove('is-wheel-driving');
            syncUi();
            return;
        }

        // Snappier rail follow — previously 0.42 felt soft/laggy under wheel input.
        viewport.scrollLeft = current + delta * 0.55;
        syncUi();
        railRaf = requestAnimationFrame(tickRail);
    };

    const queueRailScroll = (next) => {
        targetScroll = clampScroll(next);
        viewport.classList.add('is-wheel-driving');
        if (! railRaf) {
            railRaf = requestAnimationFrame(tickRail);
        }
    };

    const onWheel = (e) => {
        const max = maxScroll();
        if (max <= 0) return;

        const absX = Math.abs(e.deltaX);
        const absY = Math.abs(e.deltaY);

        // Pure horizontal trackpad pans: let native overflow-x handle them.
        if (absX > absY) {
            releaseLatch = null;
            targetScroll = viewport.scrollLeft;
            return;
        }

        if (e.deltaY === 0) return;

        // Normalize delta across devices (lines vs pixels).
        let delta = e.deltaY;
        if (e.deltaMode === 1) delta *= 16;
        if (e.deltaMode === 2) delta *= viewport.clientHeight;

        const left = viewport.scrollLeft;
        const edge = edgeTolerance();
        const atStart = left <= edge && targetScroll <= edge;
        const atEnd = left >= max - edge && targetScroll >= max - edge;
        const towardEnd = delta > 0;
        const towardStart = delta < 0;

        if (releaseLatch === 'start' && towardStart) {
            e.preventDefault();
            e.stopPropagation();
            scrollPageBy(delta);
            return;
        }

        if (releaseLatch === 'end' && towardEnd) {
            e.preventDefault();
            e.stopPropagation();
            scrollPageBy(delta);
            return;
        }

        if (releaseLatch && ((releaseLatch === 'start' && towardEnd) || (releaseLatch === 'end' && towardStart))) {
            releaseLatch = null;
        }

        const canScrollRail = (towardEnd && ! atEnd) || (towardStart && ! atStart);

        if (! canScrollRail) {
            releaseLatch = towardStart ? 'start' : 'end';
            targetScroll = towardStart ? 0 : max;
            viewport.scrollLeft = targetScroll;
            e.preventDefault();
            e.stopPropagation();
            scrollPageBy(delta);
            syncUi();
            return;
        }

        releaseLatch = null;
        e.preventDefault();
        e.stopPropagation();

        const next = clampScroll((Number.isFinite(targetScroll) ? targetScroll : left) + delta);
        queueRailScroll(next);

        if (next <= edge && towardStart) {
            releaseLatch = 'start';
            targetScroll = 0;
        } else if (next >= max - edge && towardEnd) {
            releaseLatch = 'end';
            targetScroll = max;
        }
    };

    const endWheelDrive = () => {
        viewport.classList.remove('is-wheel-driving');
    };

    if (finePointer && ! reduced) {
        viewport.addEventListener('wheel', onWheel, { passive: false, capture: true });
        viewport.addEventListener('pointerleave', endWheelDrive);
        window.addEventListener('mouseup', endWheelDrive);
        cleanups.push(() => {
            viewport.removeEventListener('wheel', onWheel, true);
            viewport.removeEventListener('pointerleave', endWheelDrive);
            window.removeEventListener('mouseup', endWheelDrive);
            if (railRaf) cancelAnimationFrame(railRaf);
            endWheelDrive();
        });
    }

    const onKey = (e) => {
        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            goPrev();
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            goNext();
        }
    };
    viewport.addEventListener('keydown', onKey);

    cleanups.push(() => {
        prevBtn?.removeEventListener('click', goPrev);
        nextBtn?.removeEventListener('click', goNext);
        viewport.removeEventListener('scroll', syncUi);
        viewport.removeEventListener('keydown', onKey);
        window.removeEventListener('resize', syncUi);
    });

    if (finePointer) {
        let down = false;
        let startX = 0;
        let startScroll = 0;
        let moved = false;

        const onDown = (e) => {
            if (e.button !== 0) return;
            if (e.target.closest('button, [data-product-action], input, select, textarea')) return;
            down = true;
            moved = false;
            startX = e.clientX;
            startScroll = viewport.scrollLeft;
            targetScroll = startScroll;
            if (railRaf) {
                cancelAnimationFrame(railRaf);
                railRaf = 0;
            }
            viewport.classList.add('is-dragging');
            releaseLatch = null;
        };
        const onMove = (e) => {
            if (! down) return;
            const dx = e.clientX - startX;
            if (Math.abs(dx) > 4) moved = true;
            const next = startScroll - dx;
            viewport.scrollLeft = next;
            targetScroll = next;
        };
        const onUp = () => {
            if (! down) return;
            down = false;
            viewport.classList.remove('is-dragging');
            syncUi();
        };
        const onClickCapture = (e) => {
            if (moved) {
                e.preventDefault();
                e.stopPropagation();
                moved = false;
            }
        };

        viewport.addEventListener('pointerdown', onDown);
        window.addEventListener('pointermove', onMove);
        window.addEventListener('pointerup', onUp);
        viewport.addEventListener('click', onClickCapture, true);
        cleanups.push(() => {
            viewport.removeEventListener('pointerdown', onDown);
            window.removeEventListener('pointermove', onMove);
            window.removeEventListener('pointerup', onUp);
            viewport.removeEventListener('click', onClickCapture, true);
        });
    }

    if (! reduced) {
        const anim = gsap.from(cards, {
            autoAlpha: 0,
            duration: 0.55,
            stagger: 0.06,
            ease: 'power2.out',
            clearProps: 'transform',
            scrollTrigger: {
                trigger: section,
                start: 'top 80%',
                once: true,
            },
        });
        cleanups.push(() => anim.scrollTrigger?.kill());
    }

    syncUi();
    requestAnimationFrame(syncUi);

    return () => cleanups.forEach((fn) => fn());
}
