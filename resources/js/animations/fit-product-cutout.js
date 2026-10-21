/**
 * Scale transparent product cutouts so the opaque bottle fills a consistent
 * share of its frame (fixes unequal visual size from uneven PNG padding).
 *
 * Opaque bounds are computed on a downsampled canvas and cached so scroll /
 * resize never re-scan full-resolution image data on the main thread.
 */

const boundsCache = new WeakMap();
const MAX_SCAN = 160;

function opaqueBounds(img) {
    if (boundsCache.has(img)) {
        return boundsCache.get(img);
    }

    const w = img.naturalWidth;
    const h = img.naturalHeight;
    if (! w || ! h) {
        return null;
    }

    const scale = Math.min(1, MAX_SCAN / Math.max(w, h));
    const sw = Math.max(1, Math.round(w * scale));
    const sh = Math.max(1, Math.round(h * scale));

    const canvas = document.createElement('canvas');
    canvas.width = sw;
    canvas.height = sh;
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    if (! ctx) {
        return null;
    }

    ctx.drawImage(img, 0, 0, sw, sh);
    let data;
    try {
        data = ctx.getImageData(0, 0, sw, sh).data;
    } catch {
        return null;
    }

    let minX = sw;
    let minY = sh;
    let maxX = 0;
    let maxY = 0;

    for (let y = 0; y < sh; y += 1) {
        const row = y * sw;
        for (let x = 0; x < sw; x += 1) {
            if (data[(row + x) * 4 + 3] > 10) {
                if (x < minX) minX = x;
                if (y < minY) minY = y;
                if (x > maxX) maxX = x;
                if (y > maxY) maxY = y;
            }
        }
    }

    if (maxX < minX || maxY < minY) {
        boundsCache.set(img, null);
        return null;
    }

    const inv = 1 / scale;
    const bounds = {
        w,
        h,
        contentW: (maxX - minX + 1) * inv,
        contentH: (maxY - minY + 1) * inv,
    };
    boundsCache.set(img, bounds);

    return bounds;
}

/**
 * @param {HTMLImageElement} img
 * @param {HTMLElement} frame
 * @param {{ fill?: number }} [options]
 */
export function fitProductCutout(img, frame, options = {}) {
    const fill = options.fill ?? 0.72;
    const bounds = opaqueBounds(img);
    if (! bounds || ! frame) {
        return;
    }

    const frameW = frame.clientWidth;
    const frameH = frame.clientHeight;
    if (frameW < 8 || frameH < 8) {
        return;
    }

    const nextScale = Math.min(
        (frameW * fill) / bounds.contentW,
        (frameH * fill) / bounds.contentH,
    );

    const width = Math.round(bounds.w * nextScale);
    const height = Math.round(bounds.h * nextScale);

    img.classList.add('is-cutout-fitted');
    img.style.width = `${width}px`;
    img.style.height = `${height}px`;
    img.style.maxWidth = 'none';
    img.style.maxHeight = 'none';
    img.style.padding = '0';
    img.style.inset = 'auto';
    img.style.left = '50%';
    img.style.top = '50%';
    // Layout centering must never tween through a diagonal transform.
    img.style.translate = '-50% -50%';
    img.style.transform = 'none';
    img.style.objectFit = 'fill';
}

/**
 * @param {ParentNode} root
 * @param {string} frameSelector
 * @param {string} imgSelector
 * @param {{ fill?: number }} [options]
 */
export function fitProductCutoutsIn(root, frameSelector, imgSelector, options = {}) {
    const frames = [...root.querySelectorAll(frameSelector)];
    const cleanups = [];
    let resizeRaf = 0;

    const applyFrame = (frame) => {
        frame.querySelectorAll(imgSelector).forEach((img) => {
            if (img.complete && img.naturalWidth) {
                fitProductCutout(img, frame, options);
            }
        });
    };

    frames.forEach((frame) => {
        const images = [...frame.querySelectorAll(imgSelector)];
        images.forEach((img) => {
            const run = () => fitProductCutout(img, frame, options);
            if (img.complete && img.naturalWidth) {
                run();
            } else {
                img.addEventListener('load', run, { once: true });
                cleanups.push(() => img.removeEventListener('load', run));
            }
        });
    });

    const onResize = () => {
        if (resizeRaf) return;
        resizeRaf = requestAnimationFrame(() => {
            resizeRaf = 0;
            // Bounds are cached — resize only reapplies CSS size from cache.
            frames.forEach(applyFrame);
        });
    };

    window.addEventListener('resize', onResize);
    cleanups.push(() => {
        window.removeEventListener('resize', onResize);
        if (resizeRaf) cancelAnimationFrame(resizeRaf);
    });

    return () => cleanups.forEach((fn) => fn());
}
