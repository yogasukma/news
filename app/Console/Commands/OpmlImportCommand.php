<?php

namespace App\Console\Commands;

use App\Models\Feed;
use App\Models\Folder;
use App\Services\OpmlParser;
use Illuminate\Console\Command;

class OpmlImportCommand extends Command
{
    protected $signature = 'rss:opml:import {file : Path to the OPML file}';

    protected $description = 'Import feeds and folders from an OPML file';

    public function handle(OpmlParser $parser): int
    {
        $filePath = $this->argument('file');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return self::FAILURE;
        }

        if (! is_readable($filePath)) {
            $this->error("File is not readable: {$filePath}");

            return self::FAILURE;
        }

        $contents = file_get_contents($filePath);

        if ($contents === false) {
            $this->error("Failed to read file: {$filePath}");

            return self::FAILURE;
        }

        try {
            $result = $parser->parse($contents);
        } catch (\Exception $e) {
            $this->error("Invalid OPML file: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info("Importing OPML: {$result['title']}");
        $this->newLine();

        // Create folders, tracking slug → folder for feed assignment
        $folderMap = [];
        $foldersAdded = 0;
        $foldersSkipped = 0;

        foreach ($result['folders'] as $folderData) {
            $existing = Folder::where('slug', $folderData['slug'])->first();

            if ($existing !== null) {
                $folderMap[$folderData['name']] = $existing;
                $foldersSkipped++;
                $this->line("  Folder '{$folderData['name']}' already exists — reusing.");

                continue;
            }

            $folderMap[$folderData['name']] = Folder::create([
                'name' => $folderData['name'],
                'slug' => $folderData['slug'],
            ]);

            $foldersAdded++;
            $this->line("  ✓ Folder '{$folderData['name']}' created.");
        }

        // Create feeds
        $feedsAdded = 0;
        $feedsSkipped = 0;

        foreach ($result['feeds'] as $feedData) {
            $existing = Feed::where('url', $feedData['url'])->first();

            if ($existing !== null) {
                $feedsSkipped++;
                $this->line("  Feed '{$feedData['title']}' already subscribed — skipping.");

                continue;
            }

            $folderId = null;

            if ($feedData['folder_name'] !== null && isset($folderMap[$feedData['folder_name']])) {
                $folderId = $folderMap[$feedData['folder_name']]->id;
            }

            Feed::create([
                'title' => $feedData['title'],
                'url' => $feedData['url'],
                'site_url' => $feedData['site_url'],
                'folder_id' => $folderId,
            ]);

            $feedsAdded++;
            $this->line("  ✓ Feed '{$feedData['title']}' added.");
        }

        $this->newLine();
        $this->info('Import complete.');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Folders created', $foldersAdded],
                ['Folders reused', $foldersSkipped],
                ['Feeds added', $feedsAdded],
                ['Feeds skipped (duplicates)', $feedsSkipped],
            ]
        );

        return self::SUCCESS;
    }
}
