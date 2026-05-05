<?php

use App\Models\Folder;

describe('rss:folder:create', function () {
    it('creates a folder with a valid name', function () {
        $this->artisan('rss:folder:create', ['name' => 'Tech'])
            ->assertSuccessful()
            ->expectsOutputToContain("Folder 'Tech' created");

        expect(Folder::where('name', 'Tech')->exists())->toBeTrue();
    });

    it('generates a slug from the name', function () {
        $this->artisan('rss:folder:create', ['name' => 'Web Development'])
            ->assertSuccessful();

        expect(Folder::first()->slug)->toBe('web-development');
    });

    it('rejects a duplicate folder name', function () {
        Folder::create(['name' => 'Tech', 'slug' => 'tech']);

        $this->artisan('rss:folder:create', ['name' => 'Tech'])
            ->assertFailed()
            ->expectsOutputToContain('already exists');
    });

    it('rejects a duplicate slug from a different name', function () {
        Folder::create(['name' => 'Tech News', 'slug' => 'tech-news']);

        $this->artisan('rss:folder:create', ['name' => 'Tech-news'])
            ->assertFailed()
            ->expectsOutputToContain('already exists');
    });
});

describe('rss:folder:list', function () {
    it('lists all folders with feed counts', function () {
        $folder = Folder::create(['name' => 'Tech', 'slug' => 'tech']);

        $this->artisan('rss:folder:list')
            ->assertSuccessful()
            ->expectsOutputToContain('Tech');
    });

    it('shows message when no folders exist', function () {
        $this->artisan('rss:folder:list')
            ->assertSuccessful()
            ->expectsOutputToContain('No folders found');
    });
});
