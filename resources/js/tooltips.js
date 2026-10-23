// One shared, text-only tooltip for the storefront and admin.
export function tooltipPosition(anchor, bubble, viewport) {
    const edge = 12;
    const gap = 9;
    const maxX = Math.max(edge, viewport.width - bubble.width - edge);
    const left = Math.min(maxX, Math.max(edge, anchor.left + anchor.width / 2 - bubble.width / 2));
    const above = anchor.top - bubble.height - gap;
    const top = Math.max(edge, Math.min(viewport.height - bubble.height - edge,
        above >= edge ? above : anchor.bottom + gap));
    return { left, top };
}

export function initTooltips() {
    if (document.querySelector('[data-site-tooltip]')) return;
    const bubble = document.createElement('div');
    bubble.id = 'site-tooltip';
    bubble.className = 'site-tooltip';
    bubble.dataset.siteTooltip = '';
    bubble.setAttribute('role', 'tooltip');
    bubble.hidden = true;
    document.body.append(bubble);
    let owner = null;
    let timer;
    let touch = false;
    let dismissed = null;

    function normalize(root) {
        if (!(root instanceof Element)) return;
        const nodes = [...root.querySelectorAll('[title]')];
        if (root.hasAttribute('title')) nodes.push(root);
        for (const node of nodes) {
            const label = node.getAttribute('title')?.trim();
            if (label) node.setAttribute('data-tooltip', label);
            node.removeAttribute('title');
        }
    }

    function hide() {
        clearTimeout(timer);
        if (owner) {
            const ids = (owner.getAttribute('aria-describedby') || '').split(/\s+/).filter((id) => id && id !== bubble.id);
            if (ids.length) owner.setAttribute('aria-describedby', ids.join(' '));
            else owner.removeAttribute('aria-describedby');
        }
        owner = null;
        bubble.hidden = true;
        bubble.removeAttribute('data-visible');
    }

    function position() {
        if (!owner?.isConnected) { hide(); return; }
        const anchor = owner.getBoundingClientRect();
        if (!anchor.width || !anchor.height || anchor.bottom < 0 || anchor.top > innerHeight || anchor.right < 0 || anchor.left > innerWidth) { hide(); return; }
        const point = tooltipPosition(anchor, bubble.getBoundingClientRect(), { width: innerWidth, height: innerHeight });
        bubble.style.left = `${point.left}px`;
        bubble.style.top = `${point.top}px`;
    }

    function show(element) {
        const label = element?.getAttribute('data-tooltip')?.trim();
        if (!label || touch || dismissed === element) return;
        clearTimeout(timer);
        if (owner !== element) hide();
        owner = element;
        bubble.textContent = label;
        bubble.hidden = false;
        const ids = new Set((element.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean));
        ids.add(bubble.id);
        element.setAttribute('aria-describedby', [...ids].join(' '));
        position();
        requestAnimationFrame(() => { if (owner === element) bubble.setAttribute('data-visible', ''); });
    }

    normalize(document.body);
    new MutationObserver((changes) => {
        for (const change of changes) {
            if (change.type === 'childList') change.addedNodes.forEach(normalize);
            else if (change.attributeName === 'title') normalize(change.target);
            if (change.target === owner && change.attributeName === 'data-tooltip') {
                if (owner.getAttribute('data-tooltip')) show(owner);
                else hide();
            }
        }
        if (owner && !owner.isConnected) hide();
    }).observe(document.body, { subtree: true, childList: true, attributes: true, attributeFilter: ['title', 'data-tooltip'] });

    document.addEventListener('pointerover', (event) => {
        if (event.pointerType === 'touch') return;
        touch = false;
        const target = event.target.closest?.('[data-tooltip]');
        if (!target || target.contains(event.relatedTarget)) return;
        dismissed = null;
        show(target);
    });
    document.addEventListener('pointerout', (event) => {
        if (!owner || !owner.contains(event.target) || owner.contains(event.relatedTarget) || bubble.contains(event.relatedTarget)) return;
        if (document.activeElement === owner) return;
        timer = setTimeout(hide, 100);
    });
    bubble.addEventListener('pointerenter', () => clearTimeout(timer));
    bubble.addEventListener('pointerleave', () => { if (document.activeElement !== owner) hide(); });
    document.addEventListener('focusin', (event) => {
        const target = event.target.closest?.('[data-tooltip]');
        dismissed = null;
        hide();
        // Native focus can scroll the control into view after focusin fires.
        if (target) requestAnimationFrame(() => { if (target.contains(document.activeElement)) show(target); });
    });
    document.addEventListener('focusout', (event) => { if (owner?.contains(event.target)) hide(); });
    document.addEventListener('pointerdown', (event) => { touch = event.pointerType === 'touch'; hide(); }, true);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') { dismissed = owner || document.activeElement?.closest?.('[data-tooltip]'); hide(); }
        if (event.key === 'Tab') touch = false;
    }, true);
    document.addEventListener('scroll', () => {
        if (owner?.contains(document.activeElement)) position();
        else hide();
    }, true);
    window.addEventListener('resize', hide);
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initTooltips, { once: true });
    else initTooltips();
}
