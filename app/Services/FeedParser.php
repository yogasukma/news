<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FeedParser
{
    /**
     * Fetch and parse a feed URL, returning normalized feed metadata and articles.
     *
     * @return array{feed: array{title: string, site_url: ?string, description: ?string}, articles: array<int, array{title: string, url: string, content: ?string, author: ?string, published_at: string, cover_image: ?string, external_id: ?string}>}
     *
     * @throws \Exception
     */
    public function parse(string $url): array
    {
        $response = Http::timeout(30)
            ->withUserAgent('RSSReader/1.0')
            ->get($url);

        if (! $response->successful()) {
            throw new \Exception("Failed to fetch feed: HTTP {$response->status()}");
        }

        $xml = $this->parseXml($response->body());

        if ($xml === null) {
            throw new \Exception('Failed to parse feed XML: invalid or malformed XML');
        }

        return $this->detectAndParse($xml, $url);
    }

    /**
     * Parse an XML string, returning null on failure instead of throwing.
     */
    public function parseXml(string $xmlString): ?\SimpleXMLElement
    {
        $previousState = libxml_use_internal_errors(true);

        try {
            $xml = simplexml_load_string($xmlString, \SimpleXMLElement::class, LIBXML_NOCDATA);

            return $xml === false ? null : $xml;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousState);
        }
    }

    /**
     * Detect feed type (RSS or Atom) and delegate to the appropriate parser.
     */
    public function detectAndParse(\SimpleXMLElement $xml, string $url): array
    {
        $root = $xml->getName();

        if ($root === 'rss' || $root === 'RDF') {
            return $this->parseRss($xml, $url);
        }

        if ($root === 'feed') {
            return $this->parseAtom($xml, $url);
        }

        throw new \Exception("Unknown feed format: root element '{$root}'");
    }

    /**
     * Parse an RSS 2.0 or RSS 1.0 (RDF) feed.
     */
    public function parseRss(\SimpleXMLElement $xml, string $url): array
    {
        $channel = $xml->getName() === 'RDF'
            ? $xml
            : $xml->channel;

        $feedTitle = (string) $channel->title;
        $feedSiteUrl = (string) ($channel->link ?? parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST));
        $feedDescription = (string) ($channel->description ?? null);

        $items = $xml->getName() === 'RDF'
            ? $xml->item
            : $channel->item;

        $articles = [];
        foreach ($items as $item) {
            $articles[] = $this->parseRssItem($item);
        }

        return [
            'feed' => [
                'title' => $feedTitle ?: parse_url($url, PHP_URL_HOST),
                'site_url' => $feedSiteUrl ?: null,
                'description' => $feedDescription ?: null,
            ],
            'articles' => $articles,
        ];
    }

    /**
     * Parse a single RSS item.
     *
     * @return array{title: string, url: string, content: ?string, author: ?string, published_at: string, cover_image: ?string, external_id: ?string}
     */
    protected function parseRssItem(\SimpleXMLElement $item): array
    {
        $content = $this->getRssContent($item);
        $coverImage = $this->getRssCoverImage($item);
        $publishedAt = $this->parseDate((string) ($item->pubDate ?? null));

        return [
            'title' => (string) $item->title,
            'url' => (string) $item->link,
            'content' => $content,
            'author' => (string) ($item->author ?? $item->children('dc', true)->creator ?? null),
            'published_at' => $publishedAt,
            'cover_image' => $coverImage,
            'external_id' => (string) ($item->guid ?? null) ?: null,
        ];
    }

    /**
     * Extract content from RSS item, checking content:encoded first then description.
     */
    protected function getRssContent(\SimpleXMLElement $item): ?string
    {
        $contentEncoded = $item->children('content', true)->encoded;

        if ($contentEncoded !== null && (string) $contentEncoded !== '') {
            return (string) $contentEncoded;
        }

        $description = $item->description;

        if ($description !== null && (string) $description !== '') {
            return (string) $description;
        }

        return null;
    }

    /**
     * Extract cover image from RSS item (enclosure, media:content, media:thumbnail).
     */
    protected function getRssCoverImage(\SimpleXMLElement $item): ?string
    {
        // Check enclosure
        $enclosure = $item->enclosure;
        if ($enclosure !== null) {
            $type = (string) $enclosure['type'];
            if (Str::startsWith($type, 'image/')) {
                return (string) $enclosure['url'];
            }
        }

        // Check media:content
        $mediaContent = $item->children('media', true)->content;
        if ($mediaContent !== null) {
            $url = (string) $mediaContent['url'];
            if ($url !== '') {
                return $url;
            }
        }

        // Check media:thumbnail
        $mediaThumbnail = $item->children('media', true)->thumbnail;
        if ($mediaThumbnail !== null) {
            $url = (string) $mediaThumbnail['url'];
            if ($url !== '') {
                return $url;
            }
        }

        return null;
    }

    /**
     * Parse an Atom feed.
     */
    public function parseAtom(\SimpleXMLElement $xml, string $url): array
    {
        $feedTitle = (string) $xml->title;
        $feedSiteUrl = $this->getAtomLink($xml, 'alternate') ?? $this->getAtomLink($xml, null) ?? parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST);
        $feedDescription = (string) ($xml->subtitle ?? null);

        $articles = [];
        foreach ($xml->entry as $entry) {
            $articles[] = $this->parseAtomEntry($entry);
        }

        return [
            'feed' => [
                'title' => $feedTitle ?: parse_url($url, PHP_URL_HOST),
                'site_url' => $feedSiteUrl ?: null,
                'description' => $feedDescription ?: null,
            ],
            'articles' => $articles,
        ];
    }

    /**
     * Parse a single Atom entry.
     *
     * @return array{title: string, url: string, content: ?string, author: ?string, published_at: string, cover_image: ?string, external_id: ?string}
     */
    protected function parseAtomEntry(\SimpleXMLElement $entry): array
    {
        $content = $this->getAtomContent($entry);
        $link = $this->getAtomLink($entry, 'alternate') ?? $this->getAtomLink($entry, null) ?? '';
        $publishedAt = $this->parseDate((string) ($entry->published ?? $entry->updated ?? null));
        $author = (string) ($entry->author->name ?? null);

        // Extract cover image from link rel="enclosure" with image type
        $coverImage = null;
        foreach ($entry->link as $linkElement) {
            $rel = (string) $linkElement['rel'];
            $type = (string) $linkElement['type'];
            if ($rel === 'enclosure' && Str::startsWith($type, 'image/')) {
                $coverImage = (string) $linkElement['href'];
                break;
            }
        }

        return [
            'title' => (string) $entry->title,
            'url' => $link,
            'content' => $content,
            'author' => $author !== '' ? $author : null,
            'published_at' => $publishedAt,
            'cover_image' => $coverImage,
            'external_id' => (string) ($entry->id ?? null) ?: null,
        ];
    }

    /**
     * Extract content from Atom entry, checking content first then summary.
     */
    protected function getAtomContent(\SimpleXMLElement $entry): ?string
    {
        $content = $entry->content;
        if ($content !== null && (string) $content !== '') {
            return (string) $content;
        }

        $summary = $entry->summary;
        if ($summary !== null && (string) $summary !== '') {
            return (string) $summary;
        }

        return null;
    }

    /**
     * Get a link from an Atom element matching the given rel attribute.
     */
    protected function getAtomLink(\SimpleXMLElement $element, ?string $rel): ?string
    {
        foreach ($element->link as $link) {
            $linkRel = (string) ($link['rel'] ?? 'alternate');

            if ($rel === null && ! isset($link['rel'])) {
                return (string) $link['href'];
            }

            if ($linkRel === $rel) {
                return (string) $link['href'];
            }
        }

        return null;
    }

    /**
     * Parse a date string into ISO 8601 format.
     */
    protected function parseDate(?string $date): string
    {
        if ($date === null || $date === '') {
            return now()->toIso8601String();
        }

        try {
            return (string) Carbon::parse($date)->toIso8601String();
        } catch (\Exception) {
            return now()->toIso8601String();
        }
    }
}
