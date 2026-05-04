<?php

namespace App\Console\Commands;

use App\Models\Feed;
use App\Models\Folder;
use Illuminate\Console\Command;

class FolderMoveCommand extends Command
{
    protected $signature = 'rss:folder:move {feed : The feed ID} {folder : The folder ID or slug}';

    protected $description = 'Move a feed into a folder';

    public function handle(): int
    {
        $feed = Feed::find((int) $this->argument('feed'));

        if ($feed === null) {
            $this->error('Feed not found.');

            return self::FAILURE;
        }

        $folder = $this->resolveFolder($this->argument('folder'));

        if ($folder === null) {
            $this->error('Folder not found.');

            return self::FAILURE;
        }

        $feed->update(['folder_id' => $folder->id]);

        $this->info("Feed '{$feed->title}' moved to folder '{$folder->name}'.");

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
