<?php

use App\Models\Feed;

describe('rss:feed:enable', function () {
    it('re-enables a disabled feed', function () {
        $feed = Feed::factory()->create([
            'title' => 'Disabled Feed',
            'is_enabled' => false,
            'error_count' => 8,
            'last_error' => 'Connection refused',
        ]);

        $this->artisan('rss:feed:enable', ['feed' => (string) $feed->id])
            ->assertSuccessful()
            ->expectsOutputToContain('re-enabled');

        $fresh = $feed->fresh();
        expect($fresh->is_enabled)->toBeTrue();
        expect($fresh->error_count)->toBe(0);
        expect($fresh->last_error)->toBeNull();
    });

    it('clears error count on a feed that is enabled but has errors', function () {
        $feed = Feed::factory()->create([
            'title' => 'Flaky Feed',
            'is_enabled' => true,
            'error_count' => 3,
            'last_error' => 'Timeout',
        ]);

        $this->artisan('rss:feed:enable', ['feed' => (string) $feed->id])
            ->assertSuccessful();

        $fresh = $feed->fresh();
        expect($fresh->is_enabled)->toBeTrue();
        expect($fresh->error_count)->toBe(0);
        expect($fresh->last_error)->toBeNull();
    });

    it('shows message when feed is already healthy', function () {
        $feed = Feed::factory()->create([
            'title' => 'Healthy Feed',
            'is_enabled' => true,
            'error_count' => 0,
            'last_error' => null,
        ]);

        $this->artisan('rss:feed:enable', ['feed' => (string) $feed->id])
            ->assertSuccessful()
            ->expectsOutputToContain('already enabled and healthy');
    });

    it('shows error for non-existent feed ID', function () {
        $this->artisan('rss:feed:enable', ['feed' => '999'])
            ->assertFailed()
            ->expectsOutputToContain('Feed not found');
    });
});
