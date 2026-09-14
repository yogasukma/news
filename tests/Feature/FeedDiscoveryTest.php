<?php

use App\Services\FeedDiscovery;
use Illuminate\Support\Facades\Http;

function htmlPageWithFeedLinks(string $links): string
{
    return "<!DOCTYPE html><html><head>{$links}</head><body><h1>Hello</h1></body></html>";
}

describe('FeedDiscovery', function () {
    it('discovers an RSS feed from an absolute link tag', function () {
        Http::fake([
            'https://example.com*' => Http::response(htmlPageWithFeedLinks(
                '<link rel="alternate" type="application/rss+xml" href="https://feeds.example.com/rss">'
            )),
        ]);

        $url = app(FeedDiscovery::class)->discover('https://example.com');

        expect($url)->toBe('https://feeds.example.com/rss');
    });

    it('discovers an Atom feed when no RSS link exists', function () {
        Http::fake([
            'https://example.com*' => Http::response(htmlPageWithFeedLinks(
                '<link rel="alternate" type="application/atom+xml" href="/atom.xml">'
            )),
        ]);

        $url = app(FeedDiscovery::class)->discover('https://example.com');

        expect($url)->toBe('https://example.com/atom.xml');
    });

    it('prefers the RSS feed link over the Atom feed link', function () {
        Http::fake([
            'https://example.com*' => Http::response(htmlPageWithFeedLinks(
                '<link rel="alternate" type="application/atom+xml" href="/atom.xml">'.
                '<link rel="alternate" type="application/rss+xml" href="/rss.xml">'
            )),
        ]);

        $url = app(FeedDiscovery::class)->discover('https://example.com');

        expect($url)->toBe('https://example.com/rss.xml');
    });

    it('resolves a root-relative feed href against the site origin', function () {
        Http::fake([
            'https://example.com*' => Http::response(htmlPageWithFeedLinks(
                '<link rel="alternate" type="application/rss+xml" href="/feed">'
            )),
        ]);

        $url = app(FeedDiscovery::class)->discover('https://example.com');

        expect($url)->toBe('https://example.com/feed');
    });

    it('resolves a protocol-relative feed href using the site scheme', function () {
        Http::fake([
            'https://example.com*' => Http::response(htmlPageWithFeedLinks(
                '<link rel="alternate" type="application/rss+xml" href="//cdn.example.com/feed.xml">'
            )),
        ]);

        $url = app(FeedDiscovery::class)->discover('https://example.com');

        expect($url)->toBe('https://cdn.example.com/feed.xml');
    });

    it('resolves a path-relative feed href against the base path', function () {
        Http::fake([
            'https://example.com*' => Http::response(htmlPageWithFeedLinks(
                '<link rel="alternate" type="application/rss+xml" href="feed.xml">'
            )),
        ]);

        $url = app(FeedDiscovery::class)->discover('https://example.com/blog');

        expect($url)->toBe('https://example.com/feed.xml');
    });

    it('matches rel attributes that are space-separated lists', function () {
        Http::fake([
            'https://example.com*' => Http::response(htmlPageWithFeedLinks(
                '<link rel="alternate alternate" type="application/rss+xml" href="/rss">'
            )),
        ]);

        $url = app(FeedDiscovery::class)->discover('https://example.com');

        expect($url)->toBe('https://example.com/rss');
    });

    it('handles uppercase type attribute values case-insensitively', function () {
        Http::fake([
            'https://example.com*' => Http::response(htmlPageWithFeedLinks(
                '<link rel="alternate" TYPE="APPLICATION/ATOM+XML" href="/atom.xml">'
            )),
        ]);

        $url = app(FeedDiscovery::class)->discover('https://example.com');

        expect($url)->toBe('https://example.com/atom.xml');
    });

    it('ignores non-feed link types and throws when the page has no feed link', function () {
        Http::fake([
            'https://example.com*' => Http::response(htmlPageWithFeedLinks(
                '<link rel="stylesheet" type="text/css" href="/style.css">'.
                '<link rel="alternate" type="text/html" href="/print">'
            )),
        ]);

        expect(fn () => app(FeedDiscovery::class)->discover('https://example.com'))
            ->toThrow(RuntimeException::class, 'No RSS/Atom feed link found on this page.');
    });

    it('throws a clear error when the website cannot be fetched', function () {
        Http::fake([
            'https://example.com*' => Http::response(null, 503),
        ]);

        expect(fn () => app(FeedDiscovery::class)->discover('https://example.com'))
            ->toThrow(RuntimeException::class, 'Failed to fetch website: HTTP 503');
    });

    it('ignores feed links pointing at non-http protocols', function () {
        Http::fake([
            'https://example.com*' => Http::response(htmlPageWithFeedLinks(
                '<link rel="alternate" type="application/rss+xml" href="javascript:alert(1)">'.
                '<link rel="alternate" type="application/rss+xml" href="ftp://example.com/feed.xml">'
            )),
        ]);

        expect(fn () => app(FeedDiscovery::class)->discover('https://example.com'))
            ->toThrow(RuntimeException::class, 'No RSS/Atom feed link found on this page.');
    });
});
