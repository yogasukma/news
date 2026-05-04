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

        $this->artisan('rss:feed:add', ['url' => 'https://example.com/feed.xml'])
            ->assertFailed()
            ->expectsOutputToContain('Already subscribed');
    });

    it('subscribes to a valid RSS feed', function () {
        Http::fake([
            'example.com/*' => Http::response(<<<'XML'
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
            'example.com/*' => Http::response(null, 500),
        ]);

        $this->artisan('rss:feed:add', ['url' => 'https://example.com/feed.xml'])
            ->assertFailed()
            ->expectsOutputToContain('Failed to fetch');
    });
});
