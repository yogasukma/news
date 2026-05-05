<?php

namespace App\Console\Commands;

use App\Models\Feed;
use Illuminate\Console\Command;

class FeedEnableCommand extends Command
{
    protected $signature = 'rss:feed:enable {feed : The ID of the feed to re-enable}';

    protected $description = 'Re-enable a disabled feed and reset its error count';

    public function handle(): int
    {
        $feed = Feed::find((int) $this->argument('feed'));

        if ($feed === null) {
            $this->error("Feed not found with ID: {$this->argument('feed')}");

            return self::FAILURE;
        }

        if ($feed->is_enabled && $feed->error_count === 0) {
            $this->info("'{$feed->title}' is already enabled and healthy.");

            return self::SUCCESS;
        }

        $feed->update([
            'is_enabled' => true,
            'error_count' => 0,
            'last_error' => null,
        ]);

        $this->info("'{$feed->title}' has been re-enabled (error count cleared).");

        return self::SUCCESS;
    }
}
