<?php

use App\Models\Article;
use App\Models\Feed;
use Carbon\Carbon;

describe('US-037: date+time on article cards in search results', function () {
    it('shows date and time on search result cards', function () {
        $feed = Feed::factory()->create(['title' => 'Test Feed']);
        Article::factory()->create([
            'feed_id' => $feed->id,
            'title' => 'Laravel 12 released',
            'published_at' => Carbon::parse('2026-05-03 14:30:00'),
        ]);

        $this->get('/search?q=Laravel')
            ->assertSuccessful()
            ->assertSee('May 3, 2:30 PM');
    });

    it('shows date and time for search results spanning multiple dates', function () {
        $feed = Feed::factory()->create(['title' => 'Test Feed']);
        Article::factory()->create([
            'feed_id' => $feed->id,
            'title' => 'Laravel article one',
            'published_at' => Carbon::parse('2026-05-03 09:00:00'),
        ]);
        Article::factory()->create([
            'feed_id' => $feed->id,
            'title' => 'Laravel article two',
            'published_at' => Carbon::parse('2026-05-05 14:30:00'),
        ]);

        $this->get('/search?q=Laravel')
            ->assertSuccessful()
            ->assertSee('May 3, 9:00 AM')
            ->assertSee('May 5, 2:30 PM');
    });

    it('shows date and time in SPA search fragment', function () {
        $feed = Feed::factory()->create(['title' => 'Test Feed']);
        Article::factory()->create([
            'feed_id' => $feed->id,
            'title' => 'Laravel article',
            'published_at' => Carbon::parse('2026-05-03 14:30:00'),
        ]);

        $this->get('/search?q=Laravel&fragment=1')
            ->assertSuccessful()
            ->assertSee('May 3, 2:30 PM');
    });
});
