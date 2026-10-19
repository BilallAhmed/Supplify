<?php

/**
 * Extract bottle crops + inpainted background plate from Hero-Banner-Teal.png.
 * Run: php scripts/extract-hero-bottles.php
 */

$srcPath = dirname(__DIR__).'/public/images/homepage/Hero-Banner-Teal.png';
$outDir = dirname(__DIR__).'/public/images/homepage/hero-layers';

if (! is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

$src = imagecreatefrompng($srcPath);
$w = imagesx($src);
$h = imagesy($src);

echo "Source {$w}x{$h}".PHP_EOL;

// Tight-ish boxes around each bottle + soft contact shadow (keep pedestals in bg).
$bottles = [
    'left' => ['xf' => 0.472, 'yf' => 0.198, 'wf' => 0.118, 'hf' => 0.548],
    'center' => ['xf' => 0.558, 'yf' => 0.088, 'wf' => 0.148, 'hf' => 0.668],
    'right' => ['xf' => 0.675, 'yf' => 0.218, 'wf' => 0.118, 'hf' => 0.538],
];

$boxes = [];
foreach ($bottles as $name => $f) {
    $boxes[$name] = [
        'x' => (int) round($w * $f['xf']),
        'y' => (int) round($h * $f['yf']),
        'w' => (int) round($w * $f['wf']),
        'h' => (int) round($h * $f['hf']),
    ];
}

// Soft elliptical mask covering bottle silhouettes (not full rectangle seams).
$mask = array_fill(0, $h, array_fill(0, $w, 0.0));
foreach ($boxes as $box) {
    $cx = $box['x'] + $box['w'] / 2;
    $cy = $box['y'] + $box['h'] * 0.46;
    $rx = $box['w'] * 0.52;
    $ry = $box['h'] * 0.52;
    $x0 = max(0, (int) floor($cx - $rx - 4));
    $x1 = min($w - 1, (int) ceil($cx + $rx + 4));
    $y0 = max(0, (int) floor($cy - $ry - 4));
    $y1 = min($h - 1, (int) ceil($cy + $ry + 4));

    for ($y = $y0; $y <= $y1; $y++) {
        for ($x = $x0; $x <= $x1; $x++) {
            $nx = ($x - $cx) / max(1, $rx);
            $ny = ($y - $cy) / max(1, $ry);
            $d = $nx * $nx + $ny * $ny;
            if ($d <= 1.0) {
                // Soft edge falloff near rim
                $m = $d > 0.78 ? (1.0 - $d) / 0.22 : 1.0;
                $mask[$y][$x] = max($mask[$y][$x], max(0.0, min(1.0, $m)));
            }
        }
    }
}

$bg = imagecreatetruecolor($w, $h);
imagealphablending($bg, false);
imagesavealpha($bg, true);
imagecopy($bg, $src, 0, 0, 0, 0, $w, $h);
imagealphablending($bg, true);

$radius = 28;
$radiusSq = $radius * $radius;

for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $m = $mask[$y][$x];
        if ($m <= 0.001) {
            continue;
        }

        $sumR = 0.0;
        $sumG = 0.0;
        $sumB = 0.0;
        $sumW = 0.0;

        for ($dy = -$radius; $dy <= $radius; $dy += 2) {
            $sy = $y + $dy;
            if ($sy < 0 || $sy >= $h) {
                continue;
            }
            $dy2 = $dy * $dy;
            for ($dx = -$radius; $dx <= $radius; $dx += 2) {
                $dx2 = $dx * $dx;
                if ($dx2 + $dy2 > $radiusSq) {
                    continue;
                }
                $sx = $x + $dx;
                if ($sx < 0 || $sx >= $w) {
                    continue;
                }
                if ($mask[$sy][$sx] > 0.15) {
                    continue;
                }
                $dist = sqrt($dx2 + $dy2) + 0.5;
                $weight = 1.0 / $dist;
                $c = imagecolorat($src, $sx, $sy);
                $sumR += (($c >> 16) & 0xFF) * $weight;
                $sumG += (($c >> 8) & 0xFF) * $weight;
                $sumB += ($c & 0xFF) * $weight;
                $sumW += $weight;
            }
        }

        if ($sumW < 0.001) {
            // Fallback: sample far left of product cluster on same row
            $c = imagecolorat($src, max(0, (int) ($w * 0.40)), $y);
            $nr = ($c >> 16) & 0xFF;
            $ng = ($c >> 8) & 0xFF;
            $nb = $c & 0xFF;
        } else {
            $nr = (int) round($sumR / $sumW);
            $ng = (int) round($sumG / $sumW);
            $nb = (int) round($sumB / $sumW);
        }

        $orig = imagecolorat($src, $x, $y);
        $or = ($orig >> 16) & 0xFF;
        $og = ($orig >> 8) & 0xFF;
        $ob = $orig & 0xFF;

        $fr = (int) round($or * (1 - $m) + $nr * $m);
        $fg = (int) round($og * (1 - $m) + $ng * $m);
        $fb = (int) round($ob * (1 - $m) + $nb * $m);
        imagesetpixel($bg, $x, $y, imagecolorallocate($bg, $fr, $fg, $fb));
    }
}

imagepng($bg, $outDir.'/hero-bg.png', 6);
echo 'Wrote hero-bg.png'.PHP_EOL;

$meta = [];
foreach ($boxes as $name => $box) {
    // Slight padding so bottle + soft shadow aren't clipped
    $pad = 4;
    $x = max(0, $box['x'] - $pad);
    $y = max(0, $box['y'] - $pad);
    $bw = min($w - $x, $box['w'] + $pad * 2);
    $bh = min($h - $y, $box['h'] + $pad * 2);

    $crop = imagecreatetruecolor($bw, $bh);
    imagealphablending($crop, false);
    imagesavealpha($crop, true);
    $transparent = imagecolorallocatealpha($crop, 0, 0, 0, 127);
    imagefilledrectangle($crop, 0, 0, $bw, $bh, $transparent);
    imagealphablending($crop, true);
    imagecopy($crop, $src, 0, 0, $x, $y, $bw, $bh);

    // Feather rectangular edges toward transparency for softer overlays
    imagealphablending($crop, false);
    for ($cy = 0; $cy < $bh; $cy++) {
        for ($cx = 0; $cx < $bw; $cx++) {
            $edge = min($cx, $cy, $bw - 1 - $cx, $bh - 1 - $cy);
            if ($edge >= 6) {
                continue;
            }
            $c = imagecolorat($crop, $cx, $cy);
            $a = ($c & 0x7F000000) >> 24;
            $r = ($c >> 16) & 0xFF;
            $g = ($c >> 8) & 0xFF;
            $b = $c & 0xFF;
            $fade = $edge / 6;
            $na = (int) round(127 - (127 - $a) * $fade);
            imagesetpixel($crop, $cx, $cy, imagecolorallocatealpha($crop, $r, $g, $b, $na));
        }
    }

    imagepng($crop, $outDir."/bottle-{$name}.png", 6);
    imagedestroy($crop);

    $meta[$name] = [
        'left' => round($x / $w * 100, 4),
        'top' => round($y / $h * 100, 4),
        'width' => round($bw / $w * 100, 4),
        'height' => round($bh / $h * 100, 4),
    ];

    echo "Wrote bottle-{$name}.png @ {$x},{$y} {$bw}x{$bh}".PHP_EOL;
}

file_put_contents($outDir.'/layers.json', json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo 'Wrote layers.json'.PHP_EOL;

imagedestroy($src);
imagedestroy($bg);
