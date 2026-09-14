<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Discovers the real RSS/Atom feed URL advertised by a website's HTML.
 *
 * Injects nothing: uses the same HTTP client conventions as FeedParser
 * (30s timeout, RSSReader/1.0 user agent) so Http::fake() patterns in tests
 * work identically.
 */
class FeedDiscovery
{
    private const RSS_TYPE = 'application/rss+xml';

    private const ATOM_TYPE = 'application/atom+xml';

    /**
     * Fetch a website page and return the absolute URL of its advertised feed.
     *
     * RSS feed links are preferred over Atom links. When the page advertises
     * no feed, a RuntimeException is thrown with a clear message.
     *
     * @throws RuntimeException When the page cannot be fetched or advertises no feed.
     */
    public function discover(string $websiteUrl): string
    {
        $response = Http::timeout(30)
            ->withUserAgent('RSSReader/1.0')
            ->get($websiteUrl);

        if (! $response->successful()) {
            throw new RuntimeException("Failed to fetch website: HTTP {$response->status()}");
        }

        $feedUrl = $this->findFeedLink($response->body(), $websiteUrl);

        if ($feedUrl === null) {
            throw new RuntimeException('No RSS/Atom feed link found on this page.');
        }

        return $feedUrl;
    }

    /**
     * Extract the first advertised feed link from an HTML document.
     *
     * RSS is preferred over Atom; within the same type the first link in
     * document order wins. Returns null when the document has no usable
     * feed link.
     */
    protected function findFeedLink(string $html, string $websiteUrl): ?string
    {
        $previousState = libxml_use_internal_errors(true);

        $rssFeedUrl = null;
        $atomFeedUrl = null;

        try {
            $dom = new DOMDocument;
            $dom->loadHTML($html, LIBXML_NONET);

            $xpath = new DOMXPath($dom);
            $links = $xpath->query("//link[contains(concat(' ', normalize-space(@rel), ' '), ' alternate ')]");

            foreach ($links as $link) {
                $attributes = $link->attributes;

                if ($attributes === null) {
                    continue;
                }

                $type = strtolower(trim((string) $attributes->getNamedItem('type')?->nodeValue));
                $href = trim((string) $attributes->getNamedItem('href')?->nodeValue);

                if ($type === '' || $href === '') {
                    continue;
                }

                $resolvedUrl = $this->resolveUrl($websiteUrl, $href);

                if ($resolvedUrl === null) {
                    continue;
                }

                if ($type === self::RSS_TYPE && $rssFeedUrl === null) {
                    $rssFeedUrl = $resolvedUrl;
                } elseif ($type === self::ATOM_TYPE && $atomFeedUrl === null) {
                    $atomFeedUrl = $resolvedUrl;
                }
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousState);
        }

        return $rssFeedUrl ?? $atomFeedUrl;
    }

    /**
     * Resolve a (possibly relative) href against a base website URL.
     *
     * Supports absolute, protocol-relative (//host/path), root-relative
     * (/path) and path-relative (path) hrefs per RFC 3986 merging. Returns
     * null when the base URL is unusable or the resolved URL is not a valid
     * http(s) URL.
     */
    public function resolveUrl(string $baseUrl, string $href): ?string
    {
        if (preg_match('/^https?:\/\//i', $href) === 1) {
            return filter_var($href, FILTER_VALIDATE_URL) !== false ? $href : null;
        }

        // Any other scheme prefix (javascript:, ftp:, file:, data:, mailto:)
        // is not a resolvable feed URL — reject it outright.
        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $href) === 1) {
            return null;
        }

        $parts = parse_url($baseUrl);

        if (! isset($parts['scheme'], $parts['host']) || ! in_array($parts['scheme'], ['http', 'https'], true)) {
            return null;
        }

        $authority = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');

        if (str_starts_with($href, '//')) {
            return $parts['scheme'].':'.$href;
        }

        if (str_starts_with($href, '/')) {
            $resolved = $authority.$href;
        } else {
            $basePath = isset($parts['path']) ? rtrim(dirname($parts['path']), '/') : '';
            $resolved = $authority.$basePath.'/'.$href;
        }

        return filter_var($resolved, FILTER_VALIDATE_URL) !== false ? $resolved : null;
    }
}
