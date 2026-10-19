<?php

namespace App\Console\Commands;

use App\Services\MediaLibraryService;
use Illuminate\Console\Command;

class MediaIndexCommand extends Command
{
    protected $signature = 'media:index';

    protected $description = 'Index existing public and storage images into the media library without copying or renaming files';

    public function handle(MediaLibraryService $library): int
    {
        $this->info('Indexing media assets…');
        $result = $library->indexExistingAssets();

        $this->table(
            ['Created', 'Updated', 'Skipped'],
            [[$result['created'], $result['updated'], $result['skipped']]]
        );

        $this->info('Done. Existing files were not moved or recompressed.');

        return self::SUCCESS;
    }
}
