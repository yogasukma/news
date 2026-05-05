<?php

namespace App\Console\Commands;

use App\Models\Feed;
use App\Models\Folder;
use Illuminate\Console\Command;

class OpmlExportCommand extends Command
{
    protected $signature = 'rss:opml:export {file : Path to write the OPML file}';

    protected $description = 'Export feeds and folders to an OPML file';

    public function handle(): int
    {
        $filePath = $this->argument('file');

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $opml = $dom->createElement('opml');
        $opml->setAttribute('version', '1.0');
        $dom->appendChild($opml);

        // Head
        $head = $dom->createElement('head');
        $title = $dom->createElement('title', 'RSS Reader Export');
        $head->appendChild($title);
        $opml->appendChild($head);

        // Body
        $body = $dom->createElement('body');

        // Uncategorized feeds first
        $uncategorizedFeeds = Feed::whereNull('folder_id')->orderBy('title')->get();

        foreach ($uncategorizedFeeds as $feed) {
            $body->appendChild($this->createFeedOutline($dom, $feed));
        }

        // Then foldered feeds
        $folders = Folder::with('feeds')->orderBy('name')->get();
        $folderCount = 0;

        foreach ($folders as $folder) {
            $folderOutline = $dom->createElement('outline');
            $folderOutline->setAttribute('text', $folder->name);
            $folderOutline->setAttribute('title', $folder->name);

            foreach ($folder->feeds as $feed) {
                $folderOutline->appendChild($this->createFeedOutline($dom, $feed));
            }

            $body->appendChild($folderOutline);
            $folderCount++;
        }

        $opml->appendChild($body);

        $xml = $dom->saveXML();

        if ($xml === false) {
            $this->error('Failed to generate OPML XML.');

            return self::FAILURE;
        }

        file_put_contents($filePath, $xml);

        $totalFeeds = $uncategorizedFeeds->count() + $folders->sum(fn (Folder $folder) => $folder->feeds->count());

        $this->info("Exported {$totalFeeds} feed(s) in {$folderCount} folder(s) to {$filePath}");

        return self::SUCCESS;
    }

    /**
     * Create a feed outline element.
     */
    private function createFeedOutline(\DOMDocument $dom, Feed $feed): \DOMElement
    {
        $outline = $dom->createElement('outline');
        $outline->setAttribute('type', 'rss');
        $outline->setAttribute('text', $feed->title);
        $outline->setAttribute('title', $feed->title);
        $outline->setAttribute('xmlUrl', $feed->url);

        if ($feed->site_url !== null) {
            $outline->setAttribute('htmlUrl', $feed->site_url);
        }

        return $outline;
    }
}
