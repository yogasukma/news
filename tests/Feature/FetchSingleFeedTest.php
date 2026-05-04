<?php

use App\Models\Feed;
use Illuminate\Support\Facades\Http;

it('fetches a single feed by ID', function () {
    $feed = Feed::factory()->create(['title' => 'Test Feed', 'url' => 'https://example.com/feed.xml']);

    $rssXml = <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <rss version="2.0">
        <channel>
            <title>Test Feed</title>
            <link>https://example.com</link>
            <item>
                <title>Test Article</title>
                <link>https://example.com/article-1</link>
                <description>Article content</description>
                <pubDate>Mon, 04 May 2026 12:00:00 +0000</pubDate>
                <guid>article-1</guid>
            </item>
        </channel>
    </rss>
    XML;

    Http::fake([
        'example.com/*' => Http::response($rssXml, 200),
    ]);

    $this->artisan('rss:fetch', ['feed' => $feed->id])
        ->expectsOutput("Fetching 'Test Feed'...")
        ->expectsOutput('Fetch complete.')
        ->assertSuccessful();

    expect($feed->fresh()->last_fetched_at)->not->toBeNull();
    expect($feed->articles()->count())->toBe(1);
});

it('shows no feeds message for non-existent feed ID', function () {
    $this->artisan('rss:fetch', ['feed' => 999])
        ->expectsOutput('No feeds to fetch.')
        ->assertSuccessful();
});

it('shows error when single feed fetch fails', function () {
    $feed = Feed::factory()->create(['title' => 'Bad Feed', 'url' => 'https://bad.example.com/feed']);

    Http::fake([
        'bad.example.com/*' => Http::response('', 500),
    ]);

    $this->artisan('rss:fetch', ['feed' => $feed->id])
        ->expectsOutput('Fetch complete.')
        ->assertSuccessful();

    expect($feed->fresh()->last_fetched_at)->toBeNull();
});
