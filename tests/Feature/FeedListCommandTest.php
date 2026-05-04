<?php

use App\Models\Feed;
use App\Models\Folder;

it('lists all feeds in a table', function () {
    $folder = Folder::create(['name' => 'Tech', 'slug' => 'tech']);
    $feed = Feed::factory()->create(['title' => 'My Feed', 'url' => 'https://example.com/feed', 'folder_id' => $folder->id]);

    $this->artisan('rss:feed:list')
        ->expectsOutputToContain('My Feed')
        ->assertSuccessful();
});

it('shows folder name for feeds in folders', function () {
    $folder = Folder::create(['name' => 'Dev', 'slug' => 'dev']);
    Feed::factory()->create(['title' => 'Dev Feed', 'folder_id' => $folder->id]);

    $this->artisan('rss:feed:list')
        ->expectsOutputToContain('Dev')
        ->assertSuccessful();
});

it('shows no feeds message when empty', function () {
    $this->artisan('rss:feed:list')
        ->expectsOutput('No feeds found.')
        ->assertSuccessful();
});
