<?php

namespace App\Services;

use Illuminate\Support\Str;

class OpmlParser
{
    /**
     * Parse an OPML XML string into a normalized structure.
     *
     * @return array{title: string, folders: array<int, array{name: string, slug: string}>, feeds: array<int, array{title: string, url: string, site_url: ?string, folder_name: ?string}>}
     *
     * @throws \Exception
     */
    public function parse(string $xmlString): array
    {
        $previousState = libxml_use_internal_errors(true);

        try {
            $xml = simplexml_load_string($xmlString, \SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousState);
        }

        if ($xml === false) {
            throw new \Exception('Failed to parse OPML: invalid or malformed XML');
        }

        return $this->extract($xml);
    }

    /**
     * Extract data from a parsed OPML document.
     */
    public function extract(\SimpleXMLElement $xml): array
    {
        $title = (string) ($xml->head->title ?? 'Imported OPML');

        $folders = [];
        $feeds = [];

        foreach ($xml->body->outline as $outline) {
            if ($this->isFeedOutline($outline)) {
                // Top-level feed (no folder)
                $feeds[] = $this->extractFeedData($outline, null);
            } else {
                // Folder outline — extract folder name and child feeds
                $folderName = (string) ($outline['title'] ?? $outline['text'] ?? '');

                if ($folderName !== '') {
                    $folders[] = [
                        'name' => $folderName,
                        'slug' => Str::slug($folderName),
                    ];

                    foreach ($outline->outline as $childOutline) {
                        if ($this->isFeedOutline($childOutline)) {
                            $feeds[] = $this->extractFeedData($childOutline, $folderName);
                        }
                    }
                }
            }
        }

        return [
            'title' => $title,
            'folders' => $folders,
            'feeds' => $feeds,
        ];
    }

    /**
     * Check if an outline element represents a feed (has xmlUrl attribute).
     */
    protected function isFeedOutline(\SimpleXMLElement $outline): bool
    {
        $xmlUrl = (string) ($outline['xmlUrl'] ?? '');

        return $xmlUrl !== '';
    }

    /**
     * Extract feed data from an outline element.
     *
     * @return array{title: string, url: string, site_url: ?string, folder_name: ?string}
     */
    protected function extractFeedData(\SimpleXMLElement $outline, ?string $folderName): array
    {
        $title = (string) ($outline['title'] ?? $outline['text'] ?? '');
        $xmlUrl = (string) ($outline['xmlUrl'] ?? '');
        $htmlUrl = (string) ($outline['htmlUrl'] ?? '');

        return [
            'title' => $title,
            'url' => $xmlUrl,
            'site_url' => $htmlUrl !== '' ? $htmlUrl : null,
            'folder_name' => $folderName,
        ];
    }
}
