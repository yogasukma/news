<?php

use App\Models\Feed;
use Illuminate\Console\Scheduling\Schedule;

describe('rss:feed:recover', function () {
    it('shows a message when there are no feeds to recover', function () {
        Feed::factory()->create(['is_enabled' => true]);
        Feed::factory()->create(['is_enabled' => false]);

        $this->artisan('rss:feed:recover')
            ->assertSuccessful()
            ->expectsOutputToContain('No feeds to recover');
    });

    it('re-enables feeds disabled for more than a month and resets the error count', function () {
        $feed = Feed::factory()->create([
            'is_enabled' => false,
            'error_count' => 8,
            'last_error' => 'Connection timed out',
        ]);

        $feed->forceFill(['updated_at' => now()->subMonths(2)])->save();

        $this->artisan('rss:feed:recover')
            ->assertSuccessful()
            ->expectsOutputToContain('Recovered 1 feed(s)')
            ->expectsOutputToContain($feed->title);

        $feed->refresh();

        expect($feed->is_enabled)->toBeTrue();
        expect($feed->error_count)->toBe(0);
        expect($feed->last_error)->toBeNull();
    });

    it('leaves feeds disabled for less than a month untouched', function () {
        $feed = Feed::factory()->create([
            'is_enabled' => false,
            'error_count' => 5,
        ]);

        $feed->forceFill(['updated_at' => now()->subWeek()])->save();

        $this->artisan('rss:feed:recover')
            ->assertSuccessful()
            ->expectsOutputToContain('No feeds to recover');

        $feed->refresh();

        expect($feed->is_enabled)->toBeFalse();
        expect($feed->error_count)->toBe(5);
    });

    it('leaves enabled feeds with errors untouched', function () {
        $feed = Feed::factory()->create([
            'is_enabled' => true,
            'error_count' => 5,
        ]);

        $feed->forceFill(['updated_at' => now()->subMonths(2)])->save();

        $this->artisan('rss:feed:recover')
            ->assertSuccessful()
            ->expectsOutputToContain('No feeds to recover');

        $feed->refresh();

        expect($feed->is_enabled)->toBeTrue();
        expect($feed->error_count)->toBe(5);
    });

    it('registers the recovery command on the daily schedule', function () {
        $schedule = app(Schedule::class);
        $events = $schedule->events();
        $recoverEvent = collect($events)->first(fn ($event) => str_contains($event->command, 'rss:feed:recover'));

        expect($recoverEvent)->not->toBeNull('rss:feed:recover should be scheduled');
        expect($recoverEvent->expression)->toBe('0 0 * * *');
    });
});
