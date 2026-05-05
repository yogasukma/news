<?php

use App\Models\Article;
use App\Models\Feed;
use App\Models\Folder;

describe('homepage', function () {
    it('shows todays articles', function () {
        $feed = Feed::factory()->create();
        Article::factory()->count(3)->today()->create(['feed_id' => $feed->id]);

        $this->get('/')
            ->assertSuccessful()
            ->assertSee($feed->title);
    });

    it('shows empty state when no articles today', function () {
        $this->get('/')
            ->assertSuccessful()
            ->assertSee('No articles found');
    });
});

describe('date navigation', function () {
    it('shows articles for a specific date', function () {
        $feed = Feed::factory()->create();
        Article::factory()->onDate('2026-05-03')->create([
            'feed_id' => $feed->id,
            'title' => 'Past Article',
        ]);

        $this->get('/date/2026-05-03')
            ->assertSuccessful()
            ->assertSee('Past Article');
    });

    it('shows 404 for invalid date format', function () {
        $this->get('/date/invalid')
            ->assertNotFound();
    });

    it('redirects future dates to today', function () {
        $this->get('/date/2099-01-01')
            ->assertSuccessful();
    });
});

describe('folder filter', function () {
    it('filters articles by folder', function () {
        $folder = Folder::create(['name' => 'Tech', 'slug' => 'tech']);
        $feedInFolder = Feed::factory()->inFolder($folder)->create(['title' => 'Tech Feed']);
        $feedOutside = Feed::factory()->create(['title' => 'Other Feed']);

        Article::factory()->today()->create(['feed_id' => $feedInFolder->id, 'title' => 'Tech Article']);
        Article::factory()->today()->create(['feed_id' => $feedOutside->id, 'title' => 'Other Article']);

        $this->get('/?folder=tech')
            ->assertSuccessful()
            ->assertSee('Tech Article')
            ->assertDontSee('Other Article');
    });
});

describe('article modal', function () {
    it('returns article as JSON', function () {
        $feed = Feed::factory()->create();
        $article = Article::factory()->today()->create(['feed_id' => $feed->id]);

        $this->getJson("/article/{$article->id}")
            ->assertSuccessful()
            ->assertJsonPath('title', $article->title)
            ->assertJsonPath('feed.title', $feed->title)
            ->assertJsonStructure(['id', 'title', 'url', 'content', 'author', 'published_at', 'cover_image', 'feed']);
    });

    it('returns 404 for missing article', function () {
        $this->getJson('/article/999')
            ->assertNotFound();
    });
});
