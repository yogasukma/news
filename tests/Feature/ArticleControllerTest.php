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

describe('per-source limit on Today page', function () {
    it('shows at most 3 articles per source in today mode', function () {
        $busyFeed = Feed::factory()->create(['title' => 'Busy Feed']);

        // 5 articles from one source; "Article 0" is the newest.
        collect([40, 30, 20, 10, 0])->each(function ($minutes) use ($busyFeed) {
            Article::factory()->create([
                'feed_id' => $busyFeed->id,
                'title' => "Busy Feed Article {$minutes}",
                'published_at' => now()->subMinutes($minutes),
            ]);
        });

        // 15 more from other sources so the raw count is >= 20 and the page
        // stays in "Today's Feeds" mode.
        Feed::factory()->count(5)->create()->each(function ($feed) {
            Article::factory()->count(3)->today()->create(['feed_id' => $feed->id]);
        });

        $response = $this->get('/');

        $response->assertSuccessful()
            ->assertSee("Today's Feeds")
            ->assertSee('Busy Feed Article 0')
            ->assertSee('Busy Feed Article 10')
            ->assertSee('Busy Feed Article 20')
            ->assertDontSee('Busy Feed Article 30')
            ->assertDontSee('Busy Feed Article 40')
            // 3 from the busy feed + 15 from the others = 18 displayed.
            ->assertSee('18 articles');
    });

    it('does not cap sources in recent mode', function () {
        $feed = Feed::factory()->create(['title' => 'Lonely Feed']);

        collect([40, 30, 20, 10, 0])->each(function ($minutes) use ($feed) {
            Article::factory()->create([
                'feed_id' => $feed->id,
                'title' => "Lonely Feed Article {$minutes}",
                'published_at' => now()->subMinutes($minutes),
            ]);
        });

        $response = $this->get('/');

        $response->assertSuccessful()
            ->assertSee('Recent Feeds')
            ->assertSee('Lonely Feed Article 0')
            ->assertSee('Lonely Feed Article 40')
            ->assertSee('5 articles');
    });

    it('does not cap sources on past date pages', function () {
        $feed = Feed::factory()->create(['title' => 'History Feed']);

        collect([1, 2, 3, 4, 5])->each(function ($n) use ($feed) {
            Article::factory()->create([
                'feed_id' => $feed->id,
                'title' => "History Feed Article {$n}",
                'published_at' => "2026-05-03 10:0{$n}:00",
            ]);
        });

        $response = $this->get('/date/2026-05-03');

        $response->assertSuccessful()
            ->assertSee('History Feed Article 1')
            ->assertSee('History Feed Article 5')
            ->assertSee('5 articles')
            ->assertDontSee("Today's Feeds");
    });
});
