"""
Tight-crop studio hero photos (keep full color, no destructive keying).

Saves JPEGs that frame the bottle closely so multiply-blend on the accent
panel drops the remaining white paper cleanly.
"""

from __future__ import annotations

from pathlib import Path

import numpy as np
from PIL import Image

DIR = Path(__file__).resolve().parents[1] / "public" / "images" / "products" / "hero"
JOBS = {
    "vitamin-e-raw.jpg": "vitamin-e.jpg",
    "vitamin-b50-raw.jpg": "vitamin-b50.jpg",
    "candida-raw.jpg": "candida-support.jpg",
    "monolaurin-raw.jpg": "monolaurin.jpg",
    "nadh-raw.jpg": "nadh.jpg",
    "l-carnitine-raw.jpg": "l-carnitine.jpg",
    "magnesium-raw.jpg": "magnesium.jpg",
}

TARGET_H = 1400


def content_bbox(rgb: np.ndarray) -> tuple[int, int, int, int]:
    r = rgb[:, :, 0].astype(np.int16)
    g = rgb[:, :, 1].astype(np.int16)
    b = rgb[:, :, 2].astype(np.int16)
    mx = np.maximum(np.maximum(r, g), b)
    mn = np.minimum(np.minimum(r, g), b)
    sat = mx - mn
    content = (sat > 12) | (mx < 200) | ((r + g + b) < 620)
    ys, xs = np.where(content)
    if len(xs) == 0:
        h, w = rgb.shape[:2]
        return 0, 0, w - 1, h - 1
    return int(xs.min()), int(ys.min()), int(xs.max()), int(ys.max())


def process(src_name: str, out_name: str) -> None:
    src = DIR / src_name
    if not src.is_file():
        print(f"Skip missing {src_name}")
        return

    im = Image.open(src).convert("RGB")
    rgb = np.asarray(im)
    h, w = rgb.shape[:2]
    x0, y0, x1, y1 = content_bbox(rgb)
    bw, bh = x1 - x0 + 1, y1 - y0 + 1
    # Tight frame around bottle (cap + base) with less leftover paper
    x0 = max(0, x0 - int(bw * 0.12))
    x1 = min(w - 1, x1 + int(bw * 0.12))
    y0 = max(0, y0 - int(bh * 0.48))
    y1 = min(h - 1, y1 + int(bh * 0.32))

    crop = im.crop((x0, y0, x1 + 1, y1 + 1))
    if crop.height > TARGET_H:
        scale = TARGET_H / crop.height
        crop = crop.resize(
            (max(1, int(crop.width * scale)), TARGET_H),
            Image.Resampling.LANCZOS,
        )
    out = DIR / out_name
    crop.save(out, format="JPEG", quality=92, optimize=True)
    print(f"Wrote {out_name} ({crop.size[0]}x{crop.size[1]})")


def main() -> None:
    for src, dst in JOBS.items():
        process(src, dst)


if __name__ == "__main__":
    main()
