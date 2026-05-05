<?php

use App\Models\Feed;
use App\Models\Folder;

it('deletes a folder and moves feeds to uncategorized', function () {
    $folder = Folder::create(['name' => 'Tech', 'slug' => 'tech']);
    $feed = Feed::factory()->create(['folder_id' => $folder->id]);

    $this->artisan('rss:folder:delete', ['folder' => $folder->id])
        ->expectsConfirmation("Delete folder 'Tech'? It has 1 feed(s) that will become uncategorized.", 'yes')
        ->expectsOutput("Folder 'Tech' deleted. 1 feed(s) moved to uncategorized.")
        ->assertSuccessful();

    expect(Folder::find($folder->id))->toBeNull();
    expect($feed->fresh()->folder_id)->toBeNull();
});

it('deletes folder by slug', function () {
    $folder = Folder::create(['name' => 'Tech', 'slug' => 'tech']);

    $this->artisan('rss:folder:delete', ['folder' => 'tech'])
        ->expectsConfirmation("Delete folder 'Tech'? It has 0 feed(s) that will become uncategorized.", 'yes')
        ->assertSuccessful();

    expect(Folder::find($folder->id))->toBeNull();
});

it('shows error for non-existent folder', function () {
    $this->artisan('rss:folder:delete', ['folder' => 999])
        ->expectsOutput('Folder not found.')
        ->assertFailed();
});

it('cancels deletion when confirmation denied', function () {
    $folder = Folder::create(['name' => 'Tech', 'slug' => 'tech']);

    $this->artisan('rss:folder:delete', ['folder' => $folder->id])
        ->expectsConfirmation("Delete folder 'Tech'? It has 0 feed(s) that will become uncategorized.", 'no')
        ->expectsOutput('Cancelled.')
        ->assertSuccessful();

    expect(Folder::find($folder->id))->not->toBeNull();
});
