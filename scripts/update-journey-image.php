<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$path = 'images/homepage/our-journey.webp';

$section = App\Models\HomepageSection::query()->where('key', 'story')->first();
if (! $section) {
    echo "story section missing\n";
    exit(1);
}

$config = $section->config ?? [];
$config['visual'] = $path;

$section->image_path = $path;
$section->config = $config;
$section->save();

echo "updated story image_path={$section->image_path}\n";
echo 'visual='.($section->config['visual'] ?? '').PHP_EOL;
echo 'file_exists='.(is_file(public_path($path)) ? 'yes' : 'no').PHP_EOL;
