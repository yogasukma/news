<?php

use App\Services\FeedParser;

describe('RSS 2.0 parsing', function () {
    it('parses a valid RSS 2.0 feed', function () {
        $parser = new FeedParser;

        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <rss version="2.0">
                <channel>
                    <title>Test Blog</title>
                    <link>https://example.com</link>
                    <description>A test blog about tech</description>
                    <item>
                        <title>First Post</title>
                        <link>https://example.com/first</link>
                        <description>This is the first post</description>
                        <author>john@example.com (John)</author>
                        <pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate>
                        <guid>https://example.com/first</guid>
                    </item>
                    <item>
                        <title>Second Post</title>
                        <link>https://example.com/second</link>
                        <description>This is the second post</description>
                        <pubDate>Mon, 04 May 2026 12:00:00 +0000</pubDate>
                        <guid>https://example.com/second</guid>
                    </item>
                </channel>
            </rss>
            XML;

        $result = $parser->parseRss($parser->parseXml($xml), 'https://example.com/feed.xml');

        expect($result['feed']['title'])->toBe('Test Blog');
        expect($result['feed']['site_url'])->toBe('https://example.com');
        expect($result['feed']['description'])->toBe('A test blog about tech');
        expect($result['articles'])->toHaveCount(2);
        expect($result['articles'][0]['title'])->toBe('First Post');
        expect($result['articles'][0]['url'])->toBe('https://example.com/first');
        expect($result['articles'][0]['content'])->toBe('This is the first post');
        expect($result['articles'][0]['author'])->toBe('john@example.com (John)');
        expect($result['articles'][0]['external_id'])->toBe('https://example.com/first');
    });

    it('parses RSS 2.0 with content:encoded', function () {
        $parser = new FeedParser;

        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
                <channel>
                    <title>Blog</title>
                    <link>https://example.com</link>
                    <item>
                        <title>Rich Post</title>
                        <link>https://example.com/rich</link>
                        <description>Short summary</description>
                        <content:encoded><![CDATA[<p>Full <strong>content</strong> here</p>]]></content:encoded>
                        <pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate>
                        <guid>rich-1</guid>
                    </item>
                </channel>
            </rss>
            XML;

        $result = $parser->parseRss($parser->parseXml($xml), 'https://example.com/feed.xml');

        expect($result['articles'][0]['content'])->toBe('<p>Full <strong>content</strong> here</p>');
    });

    it('extracts cover image from enclosure', function () {
        $parser = new FeedParser;

        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <rss version="2.0">
                <channel>
                    <title>Blog</title>
                    <link>https://example.com</link>
                    <item>
                        <title>Image Post</title>
                        <link>https://example.com/img-post</link>
                        <description>Post with image</description>
                        <enclosure url="https://example.com/image.jpg" type="image/jpeg" length="12345"/>
                        <pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate>
                        <guid>img-1</guid>
                    </item>
                </channel>
            </rss>
            XML;

        $result = $parser->parseRss($parser->parseXml($xml), 'https://example.com/feed.xml');

        expect($result['articles'][0]['cover_image'])->toBe('https://example.com/image.jpg');
    });

    it('uses fallback published_at when no date', function () {
        $parser = new FeedParser;

        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <rss version="2.0">
                <channel>
                    <title>Blog</title>
                    <link>https://example.com</link>
                    <item>
                        <title>No Date Post</title>
                        <link>https://example.com/no-date</link>
                        <description>No date</description>
                        <guid>no-date-1</guid>
                    </item>
                </channel>
            </rss>
            XML;

        $result = $parser->parseRss($parser->parseXml($xml), 'https://example.com/feed.xml');

        expect($result['articles'][0]['published_at'])->not->toBeEmpty();
    });
});

describe('Atom parsing', function () {
    it('parses a valid Atom feed', function () {
        $parser = new FeedParser;

        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <feed xmlns="http://www.w3.org/2005/Atom">
                <title>Atom Blog</title>
                <link href="https://example.com" rel="alternate"/>
                <link href="https://example.com/feed.atom" rel="self"/>
                <subtitle>An Atom feed for testing</subtitle>
                <entry>
                    <title>Atom Post One</title>
                    <link href="https://example.com/atom-1" rel="alternate"/>
                    <id>urn:uuid:atom-1</id>
                    <updated>2026-05-04T10:00:00Z</updated>
                    <published>2026-05-04T10:00:00Z</published>
                    <author><name>Jane Doe</name></author>
                    <content type="html">&lt;p&gt;Atom content&lt;/p&gt;</content>
                </entry>
                <entry>
                    <title>Atom Post Two</title>
                    <link href="https://example.com/atom-2" rel="alternate"/>
                    <id>urn:uuid:atom-2</id>
                    <updated>2026-05-04T12:00:00Z</updated>
                    <published>2026-05-04T12:00:00Z</published>
                    <summary>Atom summary</summary>
                </entry>
            </feed>
            XML;

        $result = $parser->parseAtom($parser->parseXml($xml), 'https://example.com/feed.atom');

        expect($result['feed']['title'])->toBe('Atom Blog');
        expect($result['feed']['site_url'])->toBe('https://example.com');
        expect($result['feed']['description'])->toBe('An Atom feed for testing');
        expect($result['articles'])->toHaveCount(2);
        expect($result['articles'][0]['title'])->toBe('Atom Post One');
        expect($result['articles'][0]['url'])->toBe('https://example.com/atom-1');
        expect($result['articles'][0]['content'])->toBe('<p>Atom content</p>');
        expect($result['articles'][0]['author'])->toBe('Jane Doe');
        expect($result['articles'][0]['external_id'])->toBe('urn:uuid:atom-1');
    });

    it('falls back to summary when content is missing', function () {
        $parser = new FeedParser;

        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <feed xmlns="http://www.w3.org/2005/Atom">
                <title>Blog</title>
                <link href="https://example.com" rel="alternate"/>
                <entry>
                    <title>Summary Only</title>
                    <link href="https://example.com/sum" rel="alternate"/>
                    <id>sum-1</id>
                    <updated>2026-05-04T10:00:00Z</updated>
                    <summary>Just a summary</summary>
                </entry>
            </feed>
            XML;

        $result = $parser->parseAtom($parser->parseXml($xml), 'https://example.com/feed.atom');

        expect($result['articles'][0]['content'])->toBe('Just a summary');
    });
});

describe('auto-detection', function () {
    it('detects and parses RSS 2.0', function () {
        $parser = new FeedParser;

        $xml = <<<'XML'
            <?xml version="1.0"?>
            <rss version="2.0"><channel><title>Detect RSS</title><link>https://example.com</link>
                <item><title>RSS Item</title><link>https://example.com/1</link><guid>r1</guid><pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate></item>
            </channel></rss>
            XML;

        $result = $parser->detectAndParse($parser->parseXml($xml), 'https://example.com/feed.xml');

        expect($result['feed']['title'])->toBe('Detect RSS');
    });

    it('detects and parses Atom', function () {
        $parser = new FeedParser;

        $xml = <<<'XML'
            <?xml version="1.0"?>
            <feed xmlns="http://www.w3.org/2005/Atom">
                <title>Detect Atom</title>
                <link href="https://example.com" rel="alternate"/>
                <entry><title>Atom Entry</title><link href="https://example.com/1" rel="alternate"/><id>a1</id><updated>2026-05-04T10:00:00Z</updated></entry>
            </feed>
            XML;

        $result = $parser->detectAndParse($parser->parseXml($xml), 'https://example.com/feed.atom');

        expect($result['feed']['title'])->toBe('Detect Atom');
    });

    it('throws for unknown feed format', function () {
        $parser = new FeedParser;

        $xml = '<?xml version="1.0"?><unknown><title>Bad</title></unknown>';

        $parser->detectAndParse($parser->parseXml($xml), 'https://example.com/bad.xml');
    })->throws(Exception::class, 'Unknown feed format');
});

describe('error handling', function () {
    it('returns null for malformed XML', function () {
        $parser = new FeedParser;

        expect($parser->parseXml('not xml at all'))->toBeNull();
    });

    it('returns null for empty string', function () {
        $parser = new FeedParser;

        expect($parser->parseXml(''))->toBeNull();
    });
});
