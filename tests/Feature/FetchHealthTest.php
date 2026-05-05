<?php

use App\Models\Article;
use App\Models\Feed;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Http;

// ============================================================
// US-029: Schedule automatic feed fetching every 4 hours
// ============================================================

describe('US-029: scheduler configuration', function () {
    it('registers rss:fetch on a 4-hour interval', function () {
        $schedule = app(Schedule::class);
        $events = $schedule->events();
        $fetchEvent = collect($events)->first(fn ($event) => str_contains($event->command, 'rss:fetch'));

        expect($fetchEvent)->not->toBeNull('rss:fetch should be scheduled');
        expect($fetchEvent->expression)->toBe('0 */4 * * *');
    });

    it('has withoutOverlapping on the scheduled fetch', function () {
        $schedule = app(Schedule::class);
        $events = $schedule->events();
        $fetchEvent = collect($events)->first(fn ($event) => str_contains($event->command, 'rss:fetch'));

        expect($fetchEvent)->not->toBeNull();
        expect($fetchEvent->withoutOverlapping)->toBeTrue();
    });
});

// ============================================================
// US-030: Skip articles without a publication date
// ============================================================

describe('US-030: skip undated articles', function () {
    it('skips articles with no pubDate element', function () {
        $feed = Feed::factory()->create(['url' => 'https://example.com/feed.xml']);

        Http::fake([
            'example.com/*' => Http::response(<<<'XML'
                <?xml version="1.0"?>
                <rss version="2.0"><channel><title>Blog</title><link>https://example.com</link>
                    <item><title>No Date</title><link>https://example.com/nodate</link><guid>nd-1</guid></item>
                </channel></rss>
                XML),
        ]);

        $this->artisan('rss:fetch', ['feed' => (string) $feed->id])
            ->assertSuccessful();

        expect(Article::count())->toBe(0);
    });

    it('saves articles with valid dates in the same feed', function () {
        $feed = Feed::factory()->create(['url' => 'https://example.com/feed.xml']);

        Http::fake([
            'example.com/*' => Http::response(<<<'XML'
                <?xml version="1.0"?>
                <rss version="2.0"><channel><title>Blog</title><link>https://example.com</link>
                    <item><title>Dated</title><link>https://example.com/dated</link><guid>d-1</guid><pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate></item>
                    <item><title>No Date</title><link>https://example.com/nodate</link><guid>nd-1</guid></item>
                </channel></rss>
                XML),
        ]);

        $this->artisan('rss:fetch', ['feed' => (string) $feed->id])
            ->assertSuccessful();

        expect(Article::count())->toBe(1);
        expect(Article::first()->title)->toBe('Dated');
    });

    it('shows skipped count in summary output', function () {
        $feed = Feed::factory()->create(['url' => 'https://example.com/feed.xml']);

        Http::fake([
            'example.com/*' => Http::response(<<<'XML'
                <?xml version="1.0"?>
                <rss version="2.0"><channel><title>Blog</title><link>https://example.com</link>
                    <item><title>No Date</title><link>https://example.com/nodate</link><guid>nd-1</guid></item>
                    <item><title>Also No Date</title><link>https://example.com/nodate2</link><guid>nd-2</guid></item>
                </channel></rss>
                XML),
        ]);

        $this->artisan('rss:fetch', ['feed' => (string) $feed->id])
            ->assertSuccessful()
            ->expectsOutputToContain('2 skipped (no date)')
            ->expectsOutputToContain('Skipped (no date)');
    });

    it('skips Atom entries with no updated or published date', function () {
        $feed = Feed::factory()->create(['url' => 'https://example.com/feed.atom']);

        Http::fake([
            'example.com/*' => Http::response(<<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <feed xmlns="http://www.w3.org/2005/Atom">
                    <title>Atom Blog</title>
                    <link href="https://example.com" rel="alternate"/>
                    <entry>
                        <title>No Date Entry</title>
                        <link href="https://example.com/nodate" rel="alternate"/>
                        <id>atom-nd-1</id>
                        <summary>Entry with no date</summary>
                    </entry>
                </feed>
                XML),
        ]);

        $this->artisan('rss:fetch', ['feed' => (string) $feed->id])
            ->assertSuccessful();

        expect(Article::count())->toBe(0);
    });
});

// ============================================================
// US-031: Track and auto-disable feeds with consecutive fetch errors
// ============================================================

describe('US-031: error tracking', function () {
    it('resets error_count to 0 on successful fetch', function () {
        $feed = Feed::factory()->create([
            'url' => 'https://example.com/feed.xml',
            'error_count' => 5,
            'last_error' => 'Previous error',
        ]);

        Http::fake([
            'example.com/*' => Http::response(<<<'XML'
                <?xml version="1.0"?>
                <rss version="2.0"><channel><title>Blog</title><link>https://example.com</link>
                    <item><title>Post</title><link>https://example.com/1</link><guid>p1</guid><pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate></item>
                </channel></rss>
                XML),
        ]);

        $this->artisan('rss:fetch', ['feed' => (string) $feed->id])
            ->assertSuccessful();

        $fresh = $feed->fresh();
        expect($fresh->error_count)->toBe(0);
        expect($fresh->last_error)->toBeNull();
    });

    it('increments error_count on failed fetch', function () {
        $feed = Feed::factory()->create([
            'url' => 'https://bad.com/feed.xml',
            'error_count' => 2,
        ]);

        Http::fake(['bad.com/*' => Http::response(null, 500)]);

        $this->artisan('rss:fetch', ['feed' => (string) $feed->id])
            ->assertSuccessful();

        $fresh = $feed->fresh();
        expect($fresh->error_count)->toBe(3);
        expect($fresh->last_error)->not->toBeNull();
    });

    it('stores error message in last_error', function () {
        $feed = Feed::factory()->create(['url' => 'https://bad.com/feed.xml']);

        Http::fake(['bad.com/*' => Http::response(null, 500)]);

        $this->artisan('rss:fetch', ['feed' => (string) $feed->id])
            ->assertSuccessful();

        expect($feed->fresh()->last_error)->toContain('HTTP 500');
    });

    it('auto-disables feed when error_count reaches 8', function () {
        $feed = Feed::factory()->create([
            'url' => 'https://bad.com/feed.xml',
            'error_count' => 7,
            'is_enabled' => true,
        ]);

        Http::fake(['bad.com/*' => Http::response(null, 500)]);

        $this->artisan('rss:fetch', ['feed' => (string) $feed->id])
            ->assertSuccessful();

        $fresh = $feed->fresh();
        expect($fresh->error_count)->toBe(8);
        expect($fresh->is_enabled)->toBeFalse();
    });

    it('does not disable feed at error_count 7', function () {
        $feed = Feed::factory()->create([
            'url' => 'https://bad.com/feed.xml',
            'error_count' => 6,
            'is_enabled' => true,
        ]);

        Http::fake(['bad.com/*' => Http::response(null, 500)]);

        $this->artisan('rss:fetch', ['feed' => (string) $feed->id])
            ->assertSuccessful();

        $fresh = $feed->fresh();
        expect($fresh->error_count)->toBe(7);
        expect($fresh->is_enabled)->toBeTrue();
    });

    it('skips disabled feeds when fetching all', function () {
        $enabled = Feed::factory()->create(['url' => 'https://good.com/feed.xml', 'is_enabled' => true]);
        $disabled = Feed::factory()->create(['url' => 'https://bad.com/feed.xml', 'is_enabled' => false]);

        Http::fake([
            'good.com/*' => Http::response(<<<'XML'
                <?xml version="1.0"?>
                <rss version="2.0"><channel><title>Good</title><link>https://good.com</link>
                    <item><title>Good Post</title><link>https://good.com/1</link><guid>gp1</guid><pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate></item>
                </channel></rss>
                XML),
            'bad.com/*' => Http::response(null, 500),
        ]);

        $this->artisan('rss:fetch')
            ->assertSuccessful()
            ->expectsOutputToContain('Fetching');

        // Only the enabled feed was fetched
        expect(Article::count())->toBe(1);
    });

    it('can manually fetch a disabled feed by ID', function () {
        $disabled = Feed::factory()->create([
            'url' => 'https://disabled.com/feed.xml',
            'is_enabled' => false,
            'error_count' => 8,
        ]);

        Http::fake([
            'disabled.com/*' => Http::response(<<<'XML'
                <?xml version="1.0"?>
                <rss version="2.0"><channel><title>Disabled</title><link>https://disabled.com</link>
                    <item><title>Recovered Post</title><link>https://disabled.com/1</link><guid>dp1</guid><pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate></item>
                </channel></rss>
                XML),
        ]);

        $this->artisan('rss:fetch', ['feed' => (string) $disabled->id])
            ->assertSuccessful();

        // Article was saved even though feed is disabled
        expect(Article::count())->toBe(1);
        // Error count reset on success
        expect($disabled->fresh()->error_count)->toBe(0);
    });

    it('clears error_count on success even if previously high', function () {
        $feed = Feed::factory()->create([
            'url' => 'https://example.com/feed.xml',
            'error_count' => 7,
            'last_error' => 'Server error',
        ]);

        Http::fake([
            'example.com/*' => Http::response(<<<'XML'
                <?xml version="1.0"?>
                <rss version="2.0"><channel><title>Blog</title><link>https://example.com</link>
                    <item><title>Back Online</title><link>https://example.com/1</link><guid>bo1</guid><pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate></item>
                </channel></rss>
                XML),
        ]);

        $this->artisan('rss:fetch', ['feed' => (string) $feed->id])
            ->assertSuccessful();

        $fresh = $feed->fresh();
        expect($fresh->error_count)->toBe(0);
        expect($fresh->last_error)->toBeNull();
        expect($fresh->is_enabled)->toBeTrue();
    });
});
