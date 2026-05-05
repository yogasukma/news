<?php

use App\Models\Article;
use App\Models\Feed;
use App\Models\Folder;
use Carbon\Carbon;

describe('US-035: smart homepage — recent feeds fallback', function () {
    it('shows Todays Feeds when 20 or more articles today', function () {
        $feed = Feed::factory()->create();
        Article::factory()->count(20)->today()->create(['feed_id' => $feed->id]);

        $this->get('/')
            ->assertSuccessful()
            ->assertSee("Today's Feeds")
            ->assertDontSee('Recent Feeds');
    });

    it('shows Recent Feeds when fewer than 20 articles today', function () {
        $feed = Feed::factory()->create();
        Article::factory()->count(5)->today()->create(['feed_id' => $feed->id]);

        $this->get('/')
            ->assertSuccessful()
            ->assertSee('Recent Feeds')
            ->assertDontSee("Today's Feeds");
    });

    it('shows Recent Feeds when no articles today but articles exist on previous days', function () {
        $feed = Feed::factory()->create();
        Article::factory()->count(3)->onDate('2026-05-03')->create(['feed_id' => $feed->id]);

        $response = $this->get('/');

        $response->assertSuccessful()
            ->assertSee('Recent Feeds')
            ->assertSee('3 articles');
    });

    it('backfills recent articles from previous days up to 20', function () {
        $feed = Feed::factory()->create(['title' => 'My Feed']);
        // 3 articles today
        Article::factory()->count(3)->today()->create(['feed_id' => $feed->id]);
        // 17 articles yesterday
        Article::factory()->count(17)->onDate(now()->subDay()->format('Y-m-d'))->create(['feed_id' => $feed->id]);

        $response = $this->get('/');

        $response->assertSuccessful()
            ->assertSee('Recent Feeds')
            ->assertSee('20 articles');
    });

    it('does not trigger recent mode on past dates even with fewer than 20 articles', function () {
        $feed = Feed::factory()->create();
        Article::factory()->count(3)->onDate('2026-05-03')->create(['feed_id' => $feed->id]);

        $this->get('/date/2026-05-03')
            ->assertSuccessful()
            ->assertDontSee('Recent Feeds')
            ->assertDontSee("Today's Feeds")
            ->assertSee('May 3, 2026');
    });

    it('hides date navigation in recent mode', function () {
        $feed = Feed::factory()->create();
        Article::factory()->count(5)->today()->create(['feed_id' => $feed->id]);

        $this->get('/')
            ->assertSuccessful()
            ->assertDontSee('data-spa-date');
    });

    it('shows date navigation in today mode', function () {
        $feed = Feed::factory()->create();
        Article::factory()->count(20)->today()->create(['feed_id' => $feed->id]);

        $this->get('/')
            ->assertSuccessful()
            ->assertSee('data-spa-date', false);
    });

    it('applies folder filter in recent mode', function () {
        $folder = Folder::create(['name' => 'Tech', 'slug' => 'tech']);
        $techFeed = Feed::factory()->inFolder($folder)->create(['title' => 'Tech Feed']);
        $otherFeed = Feed::factory()->create(['title' => 'Other Feed']);

        Article::factory()->count(5)->today()->create(['feed_id' => $techFeed->id, 'title' => 'Tech Article']);
        Article::factory()->count(10)->onDate(now()->subDay()->format('Y-m-d'))->create(['feed_id' => $otherFeed->id, 'title' => 'Other Article']);

        $this->get('/?folder=tech')
            ->assertSuccessful()
            ->assertSee('Recent Feeds')
            ->assertSee('Tech Article')
            ->assertDontSee('Other Article');
    });

    it('returns recent mode in SPA fragment', function () {
        $feed = Feed::factory()->create();
        Article::factory()->count(5)->today()->create(['feed_id' => $feed->id]);

        $this->get('/?fragment=1')
            ->assertSuccessful()
            ->assertSee('Recent Feeds')
            ->assertDontSee("Today's Feeds");
    });
});

describe('US-036: date+time on article cards in recent mode', function () {
    it('shows time-only on article cards in today mode', function () {
        $feed = Feed::factory()->create();
        Article::factory()->count(20)->today()->create([
            'feed_id' => $feed->id,
            'published_at' => now()->setTime(14, 30),
        ]);

        $this->get('/')
            ->assertSuccessful()
            ->assertSee("Today's Feeds")
            ->assertSee('2:30 PM')
            ->assertDontSee('Recent Feeds');
    });

    it('shows date and time on article cards in recent mode', function () {
        $feed = Feed::factory()->create();
        $date = now()->subDay();
        Article::factory()->create([
            'feed_id' => $feed->id,
            'published_at' => $date->copy()->setTime(14, 30),
            'title' => 'Yesterday Article',
        ]);

        $this->get('/')
            ->assertSuccessful()
            ->assertSee('Recent Feeds')
            ->assertSee($date->format('M j').', 2:30 PM');
    });

    it('shows time-only on article cards for past date pages', function () {
        $feed = Feed::factory()->create();
        Article::factory()->create([
            'feed_id' => $feed->id,
            'published_at' => Carbon::parse('2026-05-03 14:30:00'),
            'title' => 'Past Article',
        ]);

        $this->get('/date/2026-05-03')
            ->assertSuccessful()
            ->assertSee('2:30 PM')
            ->assertDontSee('May 3, 2:30 PM');
    });
});
