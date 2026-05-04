<?php

use App\Models\Article;
use App\Models\Feed;

it('removes a feed and its articles', function () {
    $feed = Feed::factory()->create(['title' => 'Test Feed']);
    Article::factory()->count(3)->create(['feed_id' => $feed->id]);

    $this->artisan('rss:feed:remove', ['feed' => $feed->id])
        ->expectsConfirmation("Unsubscribe from 'Test Feed'? This will delete 3 article(s).", 'yes')
        ->expectsOutput("Unsubscribed from 'Test Feed'. 3 article(s) deleted.")
        ->assertSuccessful();

    expect(Feed::find($feed->id))->toBeNull();
    expect(Article::where('feed_id', $feed->id)->count())->toBe(0);
});

it('shows error for non-existent feed', function () {
    $this->artisan('rss:feed:remove', ['feed' => 999])
        ->expectsOutput('Feed not found.')
        ->assertFailed();
});

it('cancels removal when confirmation denied', function () {
    $feed = Feed::factory()->create(['title' => 'Test Feed']);

    $this->artisan('rss:feed:remove', ['feed' => $feed->id])
        ->expectsConfirmation("Unsubscribe from 'Test Feed'? This will delete 0 article(s).", 'no')
        ->expectsOutput('Cancelled.')
        ->assertSuccessful();

    expect(Feed::find($feed->id))->not->toBeNull();
});
