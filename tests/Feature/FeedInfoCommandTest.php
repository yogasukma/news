<?php

use App\Models\Article;
use App\Models\Feed;
use App\Models\Folder;

it('shows detailed info for an existing feed', function () {
    $folder = Folder::create(['name' => 'Tech', 'slug' => 'tech']);
    $feed = Feed::factory()->create([
        'title' => 'My Feed',
        'url' => 'https://example.com/feed',
        'site_url' => 'https://example.com',
        'description' => 'A great feed',
        'folder_id' => $folder->id,
    ]);
    Article::factory()->count(5)->create(['feed_id' => $feed->id]);

    $this->artisan('rss:feed:info', ['feed' => $feed->id])
        ->expectsOutputToContain('Title:         My Feed')
        ->expectsOutputToContain('URL:           https://example.com/feed')
        ->expectsOutputToContain('Site URL:      https://example.com')
        ->expectsOutputToContain('Folder:        Tech')
        ->expectsOutputToContain('Articles:      5')
        ->expectsOutputToContain('Description:   A great feed')
        ->assertSuccessful();
});

it('shows uncategorized for feed without folder', function () {
    $feed = Feed::factory()->create(['folder_id' => null]);

    $this->artisan('rss:feed:info', ['feed' => $feed->id])
        ->expectsOutputToContain('Folder:        Uncategorized')
        ->assertSuccessful();
});

it('shows error for non-existent feed', function () {
    $this->artisan('rss:feed:info', ['feed' => 999])
        ->expectsOutput('Feed not found.')
        ->assertFailed();
});
