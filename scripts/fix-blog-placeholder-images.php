<?php

/**
 * Clear missing local featured_image paths so posts use the blog placeholder.
 */

use App\Models\Post;
use App\Support\MediaUrl;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$fixed = 0;

Post::query()->whereNotNull('featured_image')->each(function (Post $post) use (&$fixed) {
    $reference = MediaUrl::normalize($post->featured_image);
    if ($reference === null) {
        return;
    }

    if (str_starts_with($reference, 'http://') || str_starts_with($reference, 'https://')) {
        return;
    }

    if (MediaUrl::absolutePath($reference) !== null) {
        return;
    }

    $post->forceFill(['featured_image' => null])->save();
    $fixed++;
    echo "Cleared missing image on post #{$post->id} ({$post->slug}): {$reference}\n";
});

echo "Done. Fixed {$fixed} post(s).\n";
