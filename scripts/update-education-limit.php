<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\HomepageSection;

$section = HomepageSection::query()->where('key', 'education')->first();

if (! $section) {
    echo "education section missing\n";
    exit(1);
}

$config = $section->config ?? [];
$config['limit'] = 3;

$section->forceFill([
    'config' => $config,
    'cta_label' => 'View all articles',
    'cta_url' => '/blog',
])->save();

echo "education limit set to 3; CTA set to View all articles\n";
