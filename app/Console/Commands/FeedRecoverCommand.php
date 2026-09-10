<?php

namespace App\Console\Commands;

use App\Models\Feed;
use Illuminate\Console\Command;

class FeedRecoverCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rss:feed:recover';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-enable feeds that have been disabled for over a month';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $staleFeeds = Feed::query()
            ->where('is_enabled', false)
            ->where('updated_at', '<', now()->subMonth())
            ->get();

        if ($staleFeeds->isEmpty()) {
            $this->info('No feeds to recover.');

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($staleFeeds as $feed) {
            $rows[] = [$feed->title, $feed->url, $feed->error_count];

            $feed->update([
                'is_enabled' => true,
                'error_count' => 0,
                'last_error' => null,
            ]);
        }

        $this->info("Recovered {$staleFeeds->count()} feed(s):");
        $this->table(['Feed', 'URL', 'Previous errors'], $rows);

        return self::SUCCESS;
    }
}
