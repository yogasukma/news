<?php

use App\Models\Feed;
use Illuminate\Support\Facades\Http;

describe('rss:feed:add', function () {
    it('rejects an invalid URL', function () {
        $this->artisan('rss:feed:add', ['url' => 'not-a-url'])
            ->assertFailed()
            ->expectsOutputToContain('Invalid URL');
    });

    it('rejects a non-http URL', function () {
        $this->artisan('rss:feed:add', ['url' => 'ftp://example.com/feed.xml'])
            ->assertFailed()
            ->expectsOutputToContain('Invalid URL');
    });

    it('rejects a duplicate feed URL', function () {
        Feed::factory()->create(['url' => 'https://example.com/feed.xml']);

        Http::fake([
            'https://example.com*' => Http::response(<<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <rss version="2.0">
                    <channel>
                        <title>Test Blog</title>
                        <link>https://example.com</link>
                        <item>
                            <title>Post</title>
                            <link>https://example.com/post</link>
                            <guid>post-1</guid>
                            <pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate>
                        </item>
                    </channel>
                </rss>
                XML),
        ]);

        $this->artisan('rss:feed:add', ['url' => 'https://example.com/feed.xml'])
            ->assertFailed()
            ->expectsOutputToContain('Already subscribed');
    });

    it('subscribes to a valid RSS feed', function () {
        Http::fake([
            'https://example.com*' => Http::response(<<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <rss version="2.0">
                    <channel>
                        <title>Test Blog</title>
                        <link>https://example.com</link>
                        <description>A test blog</description>
                        <item>
                            <title>First Post</title>
                            <link>https://example.com/first</link>
                            <description>Hello world</description>
                            <pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate>
                            <guid>https://example.com/first</guid>
                        </item>
                    </channel>
                </rss>
                XML),
        ]);

        $this->artisan('rss:feed:add', ['url' => 'https://example.com/feed.xml'])
            ->expectsQuestion('Fetch articles now?', false)
            ->assertSuccessful()
            ->expectsOutputToContain("Subscribed to 'Test Blog'");

        expect(Feed::where('url', 'https://example.com/feed.xml')->exists())->toBeTrue();
    });

    it('reports error when feed cannot be fetched', function () {
        Http::fake([
            'https://example.com*' => Http::response(null, 500),
        ]);

        $this->artisan('rss:feed:add', ['url' => 'https://example.com/feed.xml'])
            ->assertFailed()
            ->expectsOutputToContain('Failed to fetch');
    });

    it('subscribes to a feed discovered from a website URL', function () {
        Http::fake([
            'https://example.com*' => Http::response(
                '<html><head>'.
                '<link rel="alternate" type="application/rss+xml" href="https://feeds.example.com/rss.xml">'.
                '</head><body>Welcome</body></html>'
            ),
            'feeds.example.com/*' => Http::response(<<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <rss version="2.0">
                    <channel>
                        <title>Discovered Blog</title>
                        <link>https://example.com</link>
                        <description>Found via discovery</description>
                        <item>
                            <title>Post</title>
                            <link>https://example.com/post</link>
                            <guid>post-1</guid>
                            <pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate>
                        </item>
                    </channel>
                </rss>
                XML),
        ]);

        $this->artisan('rss:feed:add', ['url' => 'https://example.com'])
            ->expectsQuestion('Fetch articles now?', false)
            ->assertSuccessful()
            ->expectsOutputToContain("Discovered feed 'Discovered Blog' at https://feeds.example.com/rss.xml")
            ->expectsOutputToContain("Subscribed to 'Discovered Blog'");

        $feed = Feed::where('url', 'https://feeds.example.com/rss.xml')->first();
        expect($feed)->not->toBeNull();
        expect($feed->site_url)->toBe('https://example.com');
    });

    it('reports already subscribed when the discovered feed URL is a duplicate', function () {
        Feed::factory()->create(['url' => 'https://feeds.example.com/rss.xml']);

        Http::fake([
            'https://example.com*' => Http::response(
                '<html><head>'.
                '<link rel="alternate" type="application/rss+xml" href="https://feeds.example.com/rss.xml">'.
                '</head><body>Welcome</body></html>'
            ),
            'feeds.example.com/*' => Http::response(<<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <rss version="2.0">
                    <channel>
                        <title>Discovered Blog</title>
                        <link>https://example.com</link>
                        <item>
                            <title>Post</title>
                            <link>https://example.com/post</link>
                            <guid>post-1</guid>
                            <pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate>
                        </item>
                    </channel>
                </rss>
                XML),
        ]);

        $this->artisan('rss:feed:add', ['url' => 'https://example.com'])
            ->assertFailed()
            ->expectsOutputToContain('Already subscribed');

        expect(Feed::count())->toBe(1);
    });

    it('reports an error when the discovered feed fails to parse', function () {
        Http::fake([
            'https://example.com*' => Http::response(
                '<html><head>'.
                '<link rel="alternate" type="application/rss+xml" href="https://feeds.example.com/rss.xml">'.
                '</head><body>Welcome</body></html>'
            ),
            'feeds.example.com/*' => Http::response('<html><body>Oops</body></html>', 200),
        ]);

        $this->artisan('rss:feed:add', ['url' => 'https://example.com'])
            ->assertFailed()
            ->expectsOutputToContain('Failed to fetch or parse feed');

        expect(Feed::count())->toBe(0);
    });

    it('reports an error when a website URL has no discoverable feed link', function () {
        Http::fake([
            'https://example.com*' => Http::response('<html><body>No feeds here</body></html>', 200),
        ]);

        $this->artisan('rss:feed:add', ['url' => 'https://example.com'])
            ->assertFailed()
            ->expectsOutputToContain('No RSS/Atom feed link found on this page');

        expect(Feed::count())->toBe(0);
    });
});
