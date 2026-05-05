<?php

use App\Models\Feed;

describe('rss:feed:health', function () {
    it('shows all feeds healthy message when no issues', function () {
        Feed::factory()->create(['error_count' => 0, 'is_enabled' => true]);

        $this->artisan('rss:feed:health')
            ->assertSuccessful()
            ->expectsOutputToContain('All feeds are healthy');
    });

    it('lists feeds with error_count > 0', function () {
        Feed::factory()->create(['title' => 'Flaky Feed', 'error_count' => 3, 'last_error' => 'HTTP 500']);

        $this->artisan('rss:feed:health')
            ->assertSuccessful()
            ->expectsOutputToContain('Flaky Feed')
            ->expectsOutputToContain('1 feed(s) with issues');
    });

    it('lists disabled feeds', function () {
        Feed::factory()->create([
            'title' => 'Dead Feed',
            'error_count' => 8,
            'is_enabled' => false,
            'last_error' => 'Connection timed out',
        ]);

        $this->artisan('rss:feed:health')
            ->assertSuccessful()
            ->expectsOutputToContain('Dead Feed')
            ->expectsOutputToContain('Re-enable disabled feeds');
    });

    it('shows error count in the table', function () {
        Feed::factory()->create(['title' => 'Errored', 'error_count' => 5, 'last_error' => 'Timeout']);

        $this->artisan('rss:feed:health')
            ->assertSuccessful()
            ->expectsOutputToContain('5');
    });

    it('does not show healthy enabled feeds with zero errors', function () {
        $healthy = Feed::factory()->create(['title' => 'Good Feed', 'error_count' => 0, 'is_enabled' => true]);
        $unhealthy = Feed::factory()->create(['title' => 'Bad Feed', 'error_count' => 2, 'is_enabled' => true]);

        $this->artisan('rss:feed:health')
            ->assertSuccessful()
            ->expectsOutputToContain('Bad Feed')
            ->expectsOutputToContain('1 feed(s) with issues');
    });
});
