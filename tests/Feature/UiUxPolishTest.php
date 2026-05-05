<?php

use App\Models\Article;
use App\Models\Feed;
use App\Models\Folder;

// ============================================================
// US-026: Show favicon before feed name
// ============================================================

describe('US-026: favicon in article cards', function () {
    it('shows favicon image before feed name when feed has favicon_url', function () {
        $feed = Feed::factory()->create([
            'favicon_url' => 'https://www.google.com/s2/favicons?domain=example.com&sz=32',
        ]);
        Article::factory()->today()->create(['feed_id' => $feed->id, 'title' => 'Test Article']);

        $response = $this->get('/');

        $response->assertSuccessful();
        $response->assertSee('s2/favicons?domain=example.com', false);
        $response->assertSee('onerror="this.style.display=\'none\'"', false);
    });

    it('shows favicon image when favicon_url is empty but site_url has domain', function () {
        $feed = Feed::factory()->create([
            'site_url' => 'https://example.com',
            'favicon_url' => null,
        ]);
        Article::factory()->today()->create(['feed_id' => $feed->id]);

        $response = $this->get('/');

        $response->assertSuccessful();
        // The accessor falls back to Google favicon service
        $response->assertSee('s2/favicons?domain=example.com', false);
    });

    it('does not show favicon img when feed has no site_url and no favicon_url', function () {
        $feed = Feed::factory()->create([
            'site_url' => null,
            'favicon_url' => null,
        ]);
        Article::factory()->today()->create(['feed_id' => $feed->id]);

        $response = $this->get('/');

        $response->assertSuccessful();
        // No favicon img should be rendered
        $response->assertDontSee('favicon', false);
    });
});

describe('US-026: favicon in article modal JSON', function () {
    it('includes favicon_url in article JSON response', function () {
        $feed = Feed::factory()->create([
            'favicon_url' => 'https://www.google.com/s2/favicons?domain=example.com&sz=32',
        ]);
        $article = Article::factory()->today()->create(['feed_id' => $feed->id]);

        $response = $this->getJson("/article/{$article->id}");

        $response->assertSuccessful();
        $response->assertJsonPath('feed.favicon_url', 'https://www.google.com/s2/favicons?domain=example.com&sz=32');
    });

    it('returns favicon_url via accessor when not stored', function () {
        $feed = Feed::factory()->create([
            'site_url' => 'https://myblog.com',
            'favicon_url' => null,
        ]);
        $article = Article::factory()->today()->create(['feed_id' => $feed->id]);

        $response = $this->getJson("/article/{$article->id}");

        $response->assertSuccessful();
        $response->assertJsonPath('feed.favicon_url', 'https://www.google.com/s2/favicons?domain=myblog.com&sz=32');
    });

    it('returns empty string for favicon_url when no site_url', function () {
        $feed = Feed::factory()->create([
            'site_url' => null,
            'favicon_url' => null,
        ]);
        $article = Article::factory()->today()->create(['feed_id' => $feed->id]);

        $response = $this->getJson("/article/{$article->id}");

        $response->assertSuccessful();
        $response->assertJsonPath('feed.favicon_url', '');
    });
});

describe('US-026: Feed model favicon accessor', function () {
    it('returns stored favicon_url when present', function () {
        $feed = Feed::factory()->create([
            'favicon_url' => 'https://example.com/icon.png',
        ]);

        expect($feed->favicon_url)->toBe('https://example.com/icon.png');
    });

    it('generates Google favicon URL from site_url domain', function () {
        $feed = Feed::factory()->create([
            'site_url' => 'https://blog.example.com/path',
            'favicon_url' => null,
        ]);

        expect($feed->favicon_url)->toBe('https://www.google.com/s2/favicons?domain=blog.example.com&sz=32');
    });

    it('returns empty string when no site_url and no favicon_url', function () {
        $feed = Feed::factory()->create([
            'site_url' => null,
            'favicon_url' => null,
        ]);

        expect($feed->favicon_url)->toBe('');
    });

    it('returns empty string for invalid site_url', function () {
        $feed = Feed::factory()->create([
            'site_url' => 'not-a-url',
            'favicon_url' => null,
        ]);

        expect($feed->favicon_url)->toBe('');
    });
});

// ============================================================
// US-027: Improve article card hover effects
// ============================================================

describe('US-027: article card hover classes', function () {
    it('includes hover shadow class on article cards', function () {
        $feed = Feed::factory()->create();
        Article::factory()->today()->create(['feed_id' => $feed->id]);

        $response = $this->get('/');

        $response->assertSuccessful();
        $response->assertSee('hover:shadow-md', false);
    });

    it('includes hover border color change on article cards', function () {
        $feed = Feed::factory()->create();
        Article::factory()->today()->create(['feed_id' => $feed->id]);

        $response = $this->get('/');

        $response->assertSuccessful();
        $response->assertSee('hover:border-stone-400', false);
    });

    it('includes hover elevation change on article cards', function () {
        $feed = Feed::factory()->create();
        Article::factory()->today()->create(['feed_id' => $feed->id]);

        $response = $this->get('/');

        $response->assertSuccessful();
        $response->assertSee('hover:-translate-y-0.5', false);
    });

    it('includes smooth transition on article cards', function () {
        $feed = Feed::factory()->create();
        Article::factory()->today()->create(['feed_id' => $feed->id]);

        $response = $this->get('/');

        $response->assertSuccessful();
        $response->assertSee('transition-all duration-200 ease-out', false);
    });

    it('includes cursor pointer on article cards', function () {
        $feed = Feed::factory()->create();
        Article::factory()->today()->create(['feed_id' => $feed->id]);

        $response = $this->get('/');

        $response->assertSuccessful();
        $response->assertSee('cursor-pointer', false);
    });
});

// ============================================================
// US-028: SPA-like navigation — fragment rendering
// ============================================================

describe('US-028: fragment parameter on index', function () {
    it('returns content without layout when fragment=1', function () {
        $feed = Feed::factory()->create();
        Article::factory()->today()->create(['feed_id' => $feed->id, 'title' => 'Fragment Article']);

        $response = $this->get('/?fragment=1');

        $response->assertSuccessful();
        $response->assertSee('Fragment Article');
        // Fragment should NOT include full HTML layout
        $response->assertDontSee('<!DOCTYPE html>', false);
        $response->assertDontSee('<html', false);
        $response->assertDontSee('RSS Reader', false);
    });

    it('returns full layout without fragment parameter', function () {
        $feed = Feed::factory()->create();
        Article::factory()->today()->create(['feed_id' => $feed->id]);

        $response = $this->get('/');

        $response->assertSuccessful();
        $response->assertSee('<!DOCTYPE html>', false);
        $response->assertSee('RSS Reader', false);
    });

    it('returns fragment for date page', function () {
        $feed = Feed::factory()->create();
        Article::factory()->onDate('2026-05-01')->create(['feed_id' => $feed->id, 'title' => 'Date Fragment']);

        $response = $this->get('/date/2026-05-01?fragment=1');

        $response->assertSuccessful();
        $response->assertSee('Date Fragment');
        $response->assertDontSee('<!DOCTYPE html>', false);
    });

    it('returns fragment with folder filter', function () {
        $folder = Folder::create(['name' => 'Tech', 'slug' => 'tech']);
        $feed = Feed::factory()->inFolder($folder)->create();
        Article::factory()->today()->create(['feed_id' => $feed->id, 'title' => 'Folder Fragment']);

        $response = $this->get('/?folder=tech&fragment=1');

        $response->assertSuccessful();
        $response->assertSee('Folder Fragment');
        $response->assertDontSee('<!DOCTYPE html>', false);
    });
});

describe('US-028: fragment parameter on search', function () {
    it('returns search fragment without layout', function () {
        $feed = Feed::factory()->create();
        Article::factory()->create([
            'feed_id' => $feed->id,
            'title' => 'Laravel Fragment Test',
            'published_at' => now(),
        ]);

        $response = $this->get('/search?q=laravel&fragment=1');

        $response->assertSuccessful();
        $response->assertSee('Laravel Fragment Test');
        $response->assertDontSee('<!DOCTYPE html>', false);
        $response->assertDontSee('RSS Reader', false);
    });

    it('returns full layout for search without fragment', function () {
        $response = $this->get('/search');

        $response->assertSuccessful();
        $response->assertSee('<!DOCTYPE html>', false);
        $response->assertSee('RSS Reader', false);
    });

    it('returns search fragment with folder filter', function () {
        $folder = Folder::create(['name' => 'News', 'slug' => 'news']);
        $feed = Feed::factory()->inFolder($folder)->create();
        Article::factory()->create([
            'feed_id' => $feed->id,
            'title' => 'News Laravel',
            'published_at' => now(),
        ]);

        $response = $this->get('/search?q=laravel&folder=news&fragment=1');

        $response->assertSuccessful();
        $response->assertSee('News Laravel');
        $response->assertDontSee('<!DOCTYPE html>', false);
    });
});

describe('US-028: SPA data attributes', function () {
    it('includes data-spa on date navigation links', function () {
        $feed = Feed::factory()->create();
        Article::factory()->today()->create(['feed_id' => $feed->id]);

        $response = $this->get('/');

        $response->assertSuccessful();
        $response->assertSee('data-spa', false);
    });

    it('includes data-spa on folder filter pills', function () {
        $folder = Folder::create(['name' => 'Tech', 'slug' => 'tech']);
        $feed = Feed::factory()->inFolder($folder)->create();
        Article::factory()->today()->create(['feed_id' => $feed->id]);

        $response = $this->get('/');

        $response->assertSuccessful();
        // The "All" pill and folder pills should have data-spa
        $content = $response->getContent();
        preg_match_all('/data-spa/', $content, $matches);
        expect(count($matches[0]))->toBeGreaterThanOrEqual(3); // prev date, next date, "All" pill, folder pill, date picker
    });

    it('includes data-spa-search on search form', function () {
        $feed = Feed::factory()->create();
        Article::factory()->today()->create(['feed_id' => $feed->id]);

        $response = $this->get('/');

        $response->assertSuccessful();
        $response->assertSee('data-spa-search', false);
    });

    it('includes data-spa-date on date picker', function () {
        $feed = Feed::factory()->create();
        Article::factory()->count(20)->today()->create(['feed_id' => $feed->id]);

        $response = $this->get('/');

        $response->assertSuccessful();
        $response->assertSee('data-spa-date', false);
    });

    it('includes data-spa on search pagination links', function () {
        $feed = Feed::factory()->create();
        Article::factory()->count(35)->create([
            'feed_id' => $feed->id,
            'title' => 'Laravel article',
            'published_at' => now()->subHours(rand(1, 100)),
        ]);

        $response = $this->get('/search?q=laravel');

        $response->assertSuccessful();
        // Pagination "Next" link should have data-spa
        $content = $response->getContent();
        expect($content)->toContain('data-spa');
    });
});

describe('US-028: SPA loading bar', function () {
    it('includes loading bar element in layout', function () {
        $feed = Feed::factory()->create();
        Article::factory()->today()->create(['feed_id' => $feed->id]);

        $response = $this->get('/');

        $response->assertSuccessful();
        $response->assertSee('id="spa-loading"', false);
        $response->assertSee('animate-spa-progress', false);
    });
});
