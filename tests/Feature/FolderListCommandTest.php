<?php

use App\Models\Feed;
use App\Models\Folder;

it('lists all folders with feed counts', function () {
    $folder1 = Folder::create(['name' => 'Dev', 'slug' => 'dev']);
    $folder2 = Folder::create(['name' => 'Blog', 'slug' => 'blog']);
    Feed::factory()->count(3)->create(['folder_id' => $folder1->id]);
    Feed::factory()->create(['folder_id' => $folder2->id]);

    $this->artisan('rss:folder:list')
        ->expectsOutputToContain('Dev')
        ->expectsOutputToContain('Blog')
        ->assertSuccessful();
});

it('shows no folders message when empty', function () {
    $this->artisan('rss:folder:list')
        ->expectsOutput('No folders found.')
        ->assertSuccessful();
});
