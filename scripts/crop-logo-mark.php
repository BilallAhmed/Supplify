<?php

$srcPath = __DIR__ . '/../public/images/supplify-logo-dark.webp';
$outPath = __DIR__ . '/../public/images/supplify-mark-dark.png';

$src = imagecreatefromwebp($srcPath);
if (! $src) {
    fwrite(STDERR, "Failed to load {$srcPath}\n");
    exit(1);
}

$w = imagesx($src);
$h = imagesy($src);
fwrite(STDOUT, "source: {$w}x{$h}\n");

// Icon is the left square of the wordmark; trim a little from the right
// so the first letter of "Supplify" never peeks into the mark.
$side = $h;
$cropW = (int) max(1, floor($side * 0.82));

$mark = imagecreatetruecolor($cropW, $side);
imagealphablending($mark, false);
imagesavealpha($mark, true);
$transparent = imagecolorallocatealpha($mark, 0, 0, 0, 127);
imagefilledrectangle($mark, 0, 0, $cropW, $side, $transparent);
imagealphablending($mark, true);
imagecopy($mark, $src, 0, 0, 0, 0, $cropW, $side);
imagealphablending($mark, false);
imagesavealpha($mark, true);

if (! imagepng($mark, $outPath)) {
    fwrite(STDERR, "Failed to write {$outPath}\n");
    exit(1);
}

fwrite(STDOUT, "wrote {$outPath} ({$cropW}x{$side})\n");
