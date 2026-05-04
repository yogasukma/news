<?php

use App\Models\Feed;
use App\Models\Folder;

it('moves a feed into a folder', function () {
    $feed = Feed::factory()->create(['title' => 'Test Feed']);
    $folder = Folder::create(['name' => 'Tech', 'slug' => 'tech']);

    $this->artisan('rss:folder:move', ['feed' => $feed->id, 'folder' => $folder->id])
        ->expectsOutput("Feed 'Test Feed' moved to folder 'Tech'.")
        ->assertSuccessful();

    expect($feed->fresh()->folder_id)->toBe($folder->id);
});

it('moves feed to folder by slug', function () {
    $feed = Feed::factory()->create();
    $folder = Folder::create(['name' => 'Dev', 'slug' => 'dev']);

    $this->artisan('rss:folder:move', ['feed' => $feed->id, 'folder' => 'dev'])
        ->assertSuccessful();

    expect($feed->fresh()->folder_id)->toBe($folder->id);
});

it('shows error for non-existent feed', function () {
    $folder = Folder::create(['name' => 'Tech', 'slug' => 'tech']);

    $this->artisan('rss:folder:move', ['feed' => 999, 'folder' => $folder->id])
        ->expectsOutput('Feed not found.')
        ->assertFailed();
});

it('shows error for non-existent folder', function () {
    $feed = Feed::factory()->create();

    $this->artisan('rss:folder:move', ['feed' => $feed->id, 'folder' => 999])
        ->expectsOutput('Folder not found.')
        ->assertFailed();
});
