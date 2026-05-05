<?php

namespace App\Console\Commands;

use App\Models\Folder;
use Illuminate\Console\Command;

class FolderDeleteCommand extends Command
{
    protected $signature = 'rss:folder:delete {folder : The folder ID or slug}';

    protected $description = 'Delete a folder (feeds moved to uncategorized)';

    public function handle(): int
    {
        $folder = $this->resolveFolder($this->argument('folder'));

        if ($folder === null) {
            $this->error('Folder not found.');

            return self::FAILURE;
        }

        $feedCount = $folder->feeds()->count();

        if (! $this->confirm("Delete folder '{$folder->name}'? It has {$feedCount} feed(s) that will become uncategorized.")) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $folder->feeds()->update(['folder_id' => null]);
        $folder->delete();

        $this->info("Folder '{$folder->name}' deleted. {$feedCount} feed(s) moved to uncategorized.");

        return self::SUCCESS;
    }

    private function resolveFolder(string $identifier): ?Folder
    {
        if (is_numeric($identifier)) {
            return Folder::find((int) $identifier);
        }

        return Folder::where('slug', $identifier)->first();
    }
}
