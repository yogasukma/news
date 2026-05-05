<?php

namespace App\Console\Commands;

use App\Models\Folder;
use Illuminate\Console\Command;

class FolderListCommand extends Command
{
    protected $signature = 'rss:folder:list';

    protected $description = 'List all folders with their feed counts';

    public function handle(): int
    {
        $folders = Folder::withCount('feeds')->orderBy('name')->get();

        if ($folders->isEmpty()) {
            $this->info('No folders found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Slug', 'Feeds'],
            $folders->map(fn (Folder $folder) => [
                $folder->id,
                $folder->name,
                $folder->slug,
                $folder->feeds_count,
            ])
        );

        return self::SUCCESS;
    }
}
