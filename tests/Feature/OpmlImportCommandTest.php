<?php

use App\Models\Feed;
use App\Models\Folder;

beforeEach(function () {
    $this->fixturesDir = base_path('tests/fixtures/opml');
});

it('imports folders and feeds from valid OPML', function () {
    $filePath = "{$this->fixturesDir}/valid.opml";

    $this->artisan('rss:opml:import', ['file' => $filePath])
        ->expectsOutput('Importing OPML: Test Subscriptions')
        ->expectsOutput('Import complete.')
        ->assertSuccessful();

    expect(Folder::count())->toBe(2);
    expect(Feed::count())->toBe(7);

    $devFolder = Folder::where('slug', 'dev')->first();
    expect($devFolder)->not->toBeNull();
    expect($devFolder->feeds)->toHaveCount(3);

    $blogFolder = Folder::where('slug', 'blog')->first();
    expect($blogFolder)->not->toBeNull();
    expect($blogFolder->feeds)->toHaveCount(2);

    // Uncategorized feeds
    $uncategorizedCount = Feed::whereNull('folder_id')->count();
    expect($uncategorizedCount)->toBe(2);
});

it('imports flat OPML with no folders', function () {
    $filePath = "{$this->fixturesDir}/no-folders.opml";

    $this->artisan('rss:opml:import', ['file' => $filePath])
        ->assertSuccessful();

    expect(Folder::count())->toBe(0);
    expect(Feed::count())->toBe(3);

    foreach (Feed::all() as $feed) {
        expect($feed->folder_id)->toBeNull();
    }
});

it('imports empty OPML without errors', function () {
    $filePath = "{$this->fixturesDir}/empty.opml";

    $this->artisan('rss:opml:import', ['file' => $filePath])
        ->assertSuccessful();

    expect(Folder::count())->toBe(0);
    expect(Feed::count())->toBe(0);
});

it('skips duplicate feeds on re-import', function () {
    $filePath = "{$this->fixturesDir}/valid.opml";

    // Import once
    $this->artisan('rss:opml:import', ['file' => $filePath])->assertSuccessful();
    expect(Feed::count())->toBe(7);

    // Import again — should skip all
    $this->artisan('rss:opml:import', ['file' => $filePath])
        ->expectsOutput('Import complete.')
        ->assertSuccessful();

    expect(Feed::count())->toBe(7); // No new feeds
    expect(Folder::count())->toBe(2); // Folders reused
});

it('reuses existing folders by slug', function () {
    Folder::create(['name' => 'Dev', 'slug' => 'dev']);
    Feed::factory()->create(['url' => 'https://laravel-news.com/feed']);

    $filePath = "{$this->fixturesDir}/valid.opml";

    $this->artisan('rss:opml:import', ['file' => $filePath])
        ->assertSuccessful();

    // Dev folder reused, not duplicated
    expect(Folder::where('slug', 'dev')->count())->toBe(1);
    // Laravel News already subscribed
    expect(Feed::where('url', 'https://laravel-news.com/feed')->count())->toBe(1);
});

it('handles special characters in feed titles', function () {
    $filePath = "{$this->fixturesDir}/special-chars.opml";

    $this->artisan('rss:opml:import', ['file' => $filePath])
        ->assertSuccessful();

    $emojiFeed = Feed::where('title', '✍ Evan Travers')->first();
    expect($emojiFeed)->not->toBeNull();

    $flowerFeed = Feed::where('title', '💐 Flower Blog')->first();
    expect($flowerFeed)->not->toBeNull();
});

it('shows error for non-existent file', function () {
    $this->artisan('rss:opml:import', ['file' => '/nonexistent/file.opml'])
        ->expectsOutput('File not found: /nonexistent/file.opml')
        ->assertFailed();
});

it('shows error for invalid OPML', function () {
    $filePath = "{$this->fixturesDir}/invalid.xml";

    $this->artisan('rss:opml:import', ['file' => $filePath])
        ->expectsOutputToContain('Invalid OPML file')
        ->assertFailed();
});

it('displays import summary', function () {
    $filePath = "{$this->fixturesDir}/valid.opml";

    $this->artisan('rss:opml:import', ['file' => $filePath])
        ->expectsOutputToContain('Folders created')
        ->expectsOutputToContain('Feeds added')
        ->assertSuccessful();
});
