<?php

use App\Models\Article;
use App\Models\Feed;
use Illuminate\Support\Facades\Http;

describe('global URL deduplication', function () {
    it('does not duplicate an article fetched twice from the same feed', function () {
        $feed = Feed::factory()->create(['url' => 'https://example.com/feed.xml']);

        $xml = <<<'XML'
            <?xml version="1.0"?>
            <rss version="2.0"><channel><title>Test</title><link>https://example.com</link>
                <item><title>First Title</title><link>https://example.com/1</link><guid>guid-1</guid><pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate></item>
            </channel></rss>
            XML;

        Http::fake(['example.com/*' => Http::response($xml)]);

        $this->artisan('rss:fetch', ['feed' => (string) $feed->id])
            ->expectsOutputToContain('1 new article(s)');

        expect(Article::count())->toBe(1);

        $this->artisan('rss:fetch', ['feed' => (string) $feed->id])
            ->expectsOutputToContain('0 new article(s)');

        expect(Article::count())->toBe(1);
    });

    it('updates mutable fields on the existing article instead of duplicating', function () {
        $feed = Feed::factory()->create(['url' => 'https://example.com/feed.xml']);

        $firstFetch = <<<'XML'
            <?xml version="1.0"?>
            <rss version="2.0"><channel><title>Test</title><link>https://example.com</link>
                <item><title>Original Title</title><link>https://example.com/1</link><description>Original body</description><guid>guid-1</guid><pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate></item>
            </channel></rss>
            XML;

        $secondFetch = <<<'XML'
            <?xml version="1.0"?>
            <rss version="2.0"><channel><title>Test</title><link>https://example.com</link>
                <item><title>Updated Title</title><link>https://example.com/1</link><description>Updated body</description><guid>guid-1</guid><pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate></item>
            </channel></rss>
            XML;

        Http::fake([
            'example.com/*' => Http::sequence()
                ->push($firstFetch)
                ->push($secondFetch),
        ]);

        $this->artisan('rss:fetch', ['feed' => (string) $feed->id]);
        $this->artisan('rss:fetch', ['feed' => (string) $feed->id]);

        expect(Article::count())->toBe(1);
        expect(Article::first()->title)->toBe('Updated Title');
        expect(Article::first()->content)->toContain('Updated body');
        expect(Article::first()->published_at->format('Y-m-d'))->toBe('2026-05-04');
    });

    it('deduplicates the same URL arriving from a second feed', function () {
        $feedA = Feed::factory()->create(['url' => 'https://blog-a.com/feed.xml']);
        $feedB = Feed::factory()->create(['url' => 'https://blog-b.com/feed.xml']);

        Http::fake([
            'blog-a.com/*' => Http::response(<<<'XML'
                <?xml version="1.0"?>
                <rss version="2.0"><channel><title>Blog A</title><link>https://blog-a.com</link>
                    <item><title>Shared Post</title><link>https://blog-a.com/shared</link><guid>a-1</guid><pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate></item>
                </channel></rss>
                XML),
            'blog-b.com/*' => Http::response(<<<'XML'
                <?xml version="1.0"?>
                <rss version="2.0"><channel><title>Blog B</title><link>https://blog-b.com</link>
                    <item><title>Shared Post (Syndicated)</title><link>https://blog-a.com/shared</link><guid>b-1</guid><pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate></item>
                </channel></rss>
                XML),
        ]);

        $this->artisan('rss:fetch', ['feed' => (string) $feedA->id]);
        expect(Article::count())->toBe(1);

        $this->artisan('rss:fetch', ['feed' => (string) $feedB->id])
            ->expectsOutputToContain('0 new article(s)');

        expect(Article::count())->toBe(1);
        expect(Article::first()->feed_id)->toBe($feedA->id);
        expect(Article::first()->title)->toBe('Shared Post (Syndicated)');
    });

    it('treats URLs differing only by tracking parameters as the same article', function () {
        $feed = Feed::factory()->create(['url' => 'https://example.com/feed.xml']);

        Http::fake([
            'example.com/*' => Http::sequence()
                ->push(<<<'XML'
                    <?xml version="1.0"?>
                    <rss version="2.0"><channel><title>Test</title><link>https://example.com</link>
                        <item><title>Post</title><link>https://example.com/1?utm_source=feed&amp;fbclid=abc123</link><guid>guid-1</guid><pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate></item>
                    </channel></rss>
                    XML)
                ->push(<<<'XML'
                    <?xml version="1.0"?>
                    <rss version="2.0"><channel><title>Test</title><link>https://example.com</link>
                        <item><title>Post</title><link>https://example.com/1?utm_source=twitter&amp;gclid=xyz</link><guid>guid-1</guid><pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate></item>
                    </channel></rss>
                    XML),
        ]);

        $this->artisan('rss:fetch', ['feed' => (string) $feed->id]);

        expect(Article::count())->toBe(1);
        expect(Article::first()->url)->toBe('https://example.com/1');

        // Second fetch — same permalink with different tracking params.
        $this->artisan('rss:fetch', ['feed' => (string) $feed->id])
            ->expectsOutputToContain('0 new article(s)');

        expect(Article::count())->toBe(1);
    });

    it('deduplicates by URL even when the guid changes or disappears', function () {
        $feed = Feed::factory()->create(['url' => 'https://example.com/feed.xml']);

        Http::fake([
            'example.com/*' => Http::sequence()
                ->push(<<<'XML'
                    <?xml version="1.0"?>
                    <rss version="2.0"><channel><title>Test</title><link>https://example.com</link>
                        <item><title>Post</title><link>https://example.com/1</link><guid>old-guid</guid><pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate></item>
                    </channel></rss>
                    XML)
                ->push(<<<'XML'
                    <?xml version="1.0"?>
                    <rss version="2.0"><channel><title>Test</title><link>https://example.com</link>
                        <item><title>Post</title><link>https://example.com/1</link><pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate></item>
                    </channel></rss>
                    XML),
        ]);

        $this->artisan('rss:fetch', ['feed' => (string) $feed->id]);
        expect(Article::first()->external_id)->toBe('old-guid');

        // Same permalink, guid gone entirely.
        $this->artisan('rss:fetch', ['feed' => (string) $feed->id])
            ->expectsOutputToContain('0 new article(s)');

        expect(Article::count())->toBe(1);
        // Existing external_id must not be clobbered.
        expect(Article::first()->external_id)->toBe('old-guid');
    });

    it('skips articles without a permalink instead of colliding on the unique index', function () {
        $feedA = Feed::factory()->create(['url' => 'https://a.com/feed.xml']);
        $feedB = Feed::factory()->create(['url' => 'https://b.com/feed.xml']);

        $xml = <<<'XML'
            <?xml version="1.0"?>
            <rss version="2.0"><channel><title>No Link Blog</title><link>https://example.com</link>
                <item><title>No Link Post</title><guid>g-1</guid><pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate></item>
            </channel></rss>
            XML;

        Http::fake([
            'a.com/*' => Http::response($xml),
            'b.com/*' => Http::response($xml),
        ]);

        $this->artisan('rss:fetch');

        expect(Article::count())->toBe(0);
        // Neither feed should error out because of a unique-index collision.
        expect($feedA->fresh()->is_enabled)->toBeTrue();
        expect($feedB->fresh()->is_enabled)->toBeTrue();
    });
});
