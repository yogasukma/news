<?php

use App\Models\Article;
use App\Models\Feed;
use App\Models\Folder;

beforeEach(function () {
    $this->folder = Folder::create(['name' => 'Tech', 'slug' => 'tech']);
    $this->feed = Feed::factory()->create(['folder_id' => $this->folder->id, 'title' => 'Laravel News']);
    $this->otherFeed = Feed::factory()->create(['folder_id' => null, 'title' => 'General Blog']);
});

it('finds articles matching query in title', function () {
    Article::factory()->create([
        'feed_id' => $this->feed->id,
        'title' => 'Laravel 12 released with new features',
        'content' => 'Some content here',
        'published_at' => now(),
    ]);
    Article::factory()->create([
        'feed_id' => $this->otherFeed->id,
        'title' => 'Unrelated article',
        'content' => 'Nothing about that topic',
        'published_at' => now(),
    ]);

    $response = $this->get('/search?q=laravel');

    $response->assertSuccessful();
    $response->assertViewHas('articles');
    expect($response->viewData('articles')->total())->toBe(1);
    $response->assertSee('Laravel 12 released');
});

it('finds articles matching query in content', function () {
    Article::factory()->create([
        'feed_id' => $this->feed->id,
        'title' => 'Weekly roundup',
        'content' => 'This week Docker announced a new version',
        'published_at' => now(),
    ]);

    $response = $this->get('/search?q=docker');

    $response->assertSuccessful();
    expect($response->viewData('articles')->total())->toBe(1);
});

it('shows no results message for non-matching query', function () {
    Article::factory()->create([
        'feed_id' => $this->feed->id,
        'title' => 'Hello World',
        'published_at' => now(),
    ]);

    $response = $this->get('/search?q=nonexistent');

    $response->assertSuccessful();
    $response->assertSee('No results found');
});

it('search is case insensitive', function () {
    Article::factory()->create([
        'feed_id' => $this->feed->id,
        'title' => 'LARAVEL IS GREAT',
        'published_at' => now(),
    ]);

    $response = $this->get('/search?q=laravel');

    expect($response->viewData('articles')->total())->toBe(1);
});

it('filters search by folder', function () {
    Article::factory()->create([
        'feed_id' => $this->feed->id,
        'title' => 'Laravel tips',
        'published_at' => now(),
    ]);
    Article::factory()->create([
        'feed_id' => $this->otherFeed->id,
        'title' => 'Laravel on other blog',
        'published_at' => now(),
    ]);

    $response = $this->get('/search?q=laravel&folder=tech');

    $response->assertSuccessful();
    expect($response->viewData('articles')->total())->toBe(1);
    $response->assertViewHas('activeFolder');
    expect($response->viewData('activeFolder')->slug)->toBe('tech');
});

it('filters search by date', function () {
    Article::factory()->create([
        'feed_id' => $this->feed->id,
        'title' => 'Laravel today',
        'published_at' => now(),
    ]);
    Article::factory()->create([
        'feed_id' => $this->feed->id,
        'title' => 'Laravel yesterday',
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get('/search?q=laravel&date='.now()->format('Y-m-d'));

    expect($response->viewData('articles')->total())->toBe(1);
    $response->assertSee('Laravel today');
});

it('combines search with date and folder filters', function () {
    Article::factory()->create([
        'feed_id' => $this->feed->id,
        'title' => 'Laravel match',
        'published_at' => now(),
    ]);
    Article::factory()->create([
        'feed_id' => $this->otherFeed->id,
        'title' => 'Laravel wrong folder',
        'published_at' => now(),
    ]);
    Article::factory()->create([
        'feed_id' => $this->feed->id,
        'title' => 'Laravel wrong date',
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get('/search?q=laravel&folder=tech&date='.now()->format('Y-m-d'));

    expect($response->viewData('articles')->total())->toBe(1);
    $response->assertSee('Laravel match');
});

it('shows folder filter pills on search page', function () {
    $response = $this->get('/search?q=test');

    $response->assertSuccessful();
    $response->assertSee('Tech');
    $response->assertSee('All');
});

it('shows search page without query', function () {
    $response = $this->get('/search');

    $response->assertSuccessful();
    $response->assertSee('Search');
    $response->assertSee('Enter a search term');
});

it('paginates search results', function () {
    Article::factory()->count(35)->create([
        'feed_id' => $this->feed->id,
        'title' => 'Laravel article',
        'published_at' => now()->subHours(rand(1, 100)),
    ]);

    $response = $this->get('/search?q=laravel');

    $response->assertSuccessful();
    expect($response->viewData('articles')->count())->toBeLessThanOrEqual(30);
});
