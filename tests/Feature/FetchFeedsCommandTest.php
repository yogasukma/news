<?php

use App\Models\Article;
use App\Models\Feed;
use Illuminate\Support\Facades\Http;

describe('rss:fetch', function () {
    it('shows message when no feeds exist', function () {
        $this->artisan('rss:fetch')
            ->assertSuccessful()
            ->expectsOutputToContain('No feeds to fetch');
    });

    it('fetches a single feed by ID', function () {
        $feed = Feed::factory()->create(['url' => 'https://example.com/feed.xml']);

        Http::fake([
            'example.com/*' => Http::response(<<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <rss version="2.0">
                    <channel>
                        <title>Test Blog</title>
                        <link>https://example.com</link>
                        <item>
                            <title>First Post</title>
                            <link>https://example.com/first</link>
                            <description>Hello world</description>
                            <pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate>
                            <guid>post-1</guid>
                        </item>
                        <item>
                            <title>Second Post</title>
                            <link>https://example.com/second</link>
                            <description>Another post</description>
                            <pubDate>Mon, 04 May 2026 12:00:00 +0000</pubDate>
                            <guid>post-2</guid>
                        </item>
                    </channel>
                </rss>
                XML),
        ]);

        $this->artisan('rss:fetch', ['feed' => (string) $feed->id])
            ->assertSuccessful()
            ->expectsOutputToContain('2 new article(s)');

        expect(Article::count())->toBe(2);
        expect($feed->fresh()->last_fetched_at)->not->toBeNull();
    });

    it('fetches all feeds when no ID given', function () {
        $feed1 = Feed::factory()->create(['url' => 'https://blog1.com/feed.xml']);
        $feed2 = Feed::factory()->create(['url' => 'https://blog2.com/feed.xml']);

        Http::fake([
            'blog1.com/*' => Http::response(<<<'XML'
                <?xml version="1.0"?>
                <rss version="2.0"><channel><title>Blog1</title><link>https://blog1.com</link>
                    <item><title>Post 1</title><link>https://blog1.com/1</link><guid>p1</guid><pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate></item>
                </channel></rss>
                XML),
            'blog2.com/*' => Http::response(<<<'XML'
                <?xml version="1.0"?>
                <rss version="2.0"><channel><title>Blog2</title><link>https://blog2.com</link>
                    <item><title>Post 2</title><link>https://blog2.com/2</link><guid>p2</guid><pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate></item>
                </channel></rss>
                XML),
        ]);

        $this->artisan('rss:fetch')
            ->assertSuccessful()
            ->expectsOutputToContain('Fetch complete');

        expect(Article::count())->toBe(2);
    });

    it('continues fetching when one feed fails', function () {
        $feed1 = Feed::factory()->create(['url' => 'https://bad.com/feed.xml']);
        $feed2 = Feed::factory()->create(['url' => 'https://good.com/feed.xml']);

        Http::fake([
            'bad.com/*' => Http::response(null, 500),
            'good.com/*' => Http::response(<<<'XML'
                <?xml version="1.0"?>
                <rss version="2.0"><channel><title>Good</title><link>https://good.com</link>
                    <item><title>Good Post</title><link>https://good.com/1</link><guid>gp1</guid><pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate></item>
                </channel></rss>
                XML),
        ]);

        $this->artisan('rss:fetch')
            ->assertSuccessful()
            ->expectsOutputToContain('1 new article(s)')
            ->expectsOutputToContain('Errors')
            ->expectsOutputToContain('1');
    });

    it('deduplicates articles on subsequent fetches', function () {
        $feed = Feed::factory()->create(['url' => 'https://example.com/feed.xml']);

        $xml = <<<'XML'
            <?xml version="1.0"?>
            <rss version="2.0"><channel><title>Test</title><link>https://example.com</link>
                <item><title>Existing Post</title><link>https://example.com/1</link><guid>unique-1</guid><pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate></item>
            </channel></rss>
            XML;

        Http::fake(['example.com/*' => Http::response($xml)]);

        // First fetch
        $this->artisan('rss:fetch', ['feed' => (string) $feed->id]);
        expect(Article::count())->toBe(1);

        // Second fetch — same articles
        $this->artisan('rss:fetch', ['feed' => (string) $feed->id]);
        expect(Article::count())->toBe(1); // No duplicates
    });
});
