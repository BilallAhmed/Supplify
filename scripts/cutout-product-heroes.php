<?php

/**
 * Remove near-white studio backgrounds from product hero JPEGs → transparent PNGs.
 * Scales down first to keep memory under control.
 */

ini_set('memory_limit', '512M');

$dir = dirname(__DIR__).'/public/images/products/hero';
$jobs = [
    'vitamin-e-raw.jpg' => 'vitamin-e.png',
    'vitamin-b50-raw.jpg' => 'vitamin-b50.png',
    'candida-raw.jpg' => 'candida-support.png',
    'monolaurin-raw.jpg' => 'monolaurin.png',
    'nadh-raw.jpg' => 'nadh.png',
    'l-carnitine-raw.jpg' => 'l-carnitine.png',
    'magnesium-raw.jpg' => 'magnesium.png',
];

foreach ($jobs as $in => $out) {
    $path = $dir.'/'.$in;
    if (! is_readable($path)) {
        echo "Skip missing {$in}".PHP_EOL;
        continue;
    }

    $src = @imagecreatefromjpeg($path);
    if (! $src) {
        echo "Fail read {$in}".PHP_EOL;
        continue;
    }

    $ow = imagesx($src);
    $oh = imagesy($src);
    $targetH = 1200;
    $scale = min(1, $targetH / $oh);
    $w = max(1, (int) round($ow * $scale));
    $h = max(1, (int) round($oh * $scale));

    $img = imagecreatetruecolor($w, $h);
    imagecopyresampled($img, $src, 0, 0, 0, 0, $w, $h, $ow, $oh);
    imagedestroy($src);

    $corners = [
        imagecolorat($img, 4, 4),
        imagecolorat($img, $w - 5, 4),
        imagecolorat($img, 4, $h - 5),
        imagecolorat($img, $w - 5, $h - 5),
    ];
    $cr = $cg = $cb = 0;
    foreach ($corners as $c) {
        $cr += ($c >> 16) & 0xFF;
        $cg += ($c >> 8) & 0xFF;
        $cb += $c & 0xFF;
    }
    $cr = (int) round($cr / 4);
    $cg = (int) round($cg / 4);
    $cb = (int) round($cb / 4);

    $dst = imagecreatetruecolor($w, $h);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefilledrectangle($dst, 0, 0, $w, $h, $transparent);

    $minX = $w;
    $minY = $h;
    $maxX = 0;
    $maxY = 0;

    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $c = imagecolorat($img, $x, $y);
            $r = ($c >> 16) & 0xFF;
            $g = ($c >> 8) & 0xFF;
            $b = $c & 0xFF;

            $dr = $r - $cr;
            $dg = $g - $cg;
            $db = $b - $cb;
            $dist = sqrt($dr * $dr + $dg * $dg + $db * $db);

            $isBright = ($r + $g + $b) > 560;
            if ($dist < 32 && $isBright) {
                continue;
            }
            if ($dist < 48 && $r > 190 && $g > 190 && $b > 190) {
                $t = ($dist - 32) / 16;
                $alpha = (int) round(127 * (1 - $t));
                imagesetpixel($dst, $x, $y, imagecolorallocatealpha($dst, $r, $g, $b, max(0, min(127, $alpha))));
                continue;
            }

            imagesetpixel($dst, $x, $y, imagecolorallocatealpha($dst, $r, $g, $b, 0));
            if ($x < $minX) {
                $minX = $x;
            }
            if ($y < $minY) {
                $minY = $y;
            }
            if ($x > $maxX) {
                $maxX = $x;
            }
            if ($y > $maxY) {
                $maxY = $y;
            }
        }
    }

    imagedestroy($img);

    if ($maxX <= $minX || $maxY <= $minY) {
        echo "Empty cutout for {$in}".PHP_EOL;
        imagedestroy($dst);
        continue;
    }

    $pad = (int) round(min($w, $h) * 0.02);
    $minX = max(0, $minX - $pad);
    $minY = max(0, $minY - $pad);
    $maxX = min($w - 1, $maxX + $pad);
    $maxY = min($h - 1, $maxY + $pad);
    $cw = $maxX - $minX + 1;
    $ch = $maxY - $minY + 1;

    $crop = imagecreatetruecolor($cw, $ch);
    imagealphablending($crop, false);
    imagesavealpha($crop, true);
    imagefilledrectangle($crop, 0, 0, $cw, $ch, $transparent);
    imagecopy($crop, $dst, 0, 0, $minX, $minY, $cw, $ch);
    imagedestroy($dst);

    imagepng($crop, $dir.'/'.$out, 6);
    echo "Wrote {$out} ({$cw}x{$ch})".PHP_EOL;
    imagedestroy($crop);
}
