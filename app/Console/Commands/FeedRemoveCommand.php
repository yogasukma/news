<?php

namespace App\Console\Commands;

use App\Models\Feed;
use Illuminate\Console\Command;

class FeedRemoveCommand extends Command
{
    protected $signature = 'rss:feed:remove {feed : The feed ID}';

    protected $description = 'Unsubscribe from a feed and delete its articles';

    public function handle(): int
    {
        $feed = Feed::find((int) $this->argument('feed'));

        if ($feed === null) {
            $this->error('Feed not found.');

            return self::FAILURE;
        }

        $articleCount = $feed->articles()->count();

        if (! $this->confirm("Unsubscribe from '{$feed->title}'? This will delete {$articleCount} article(s).")) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $feed->delete();

        $this->info("Unsubscribed from '{$feed->title}'. {$articleCount} article(s) deleted.");

        return self::SUCCESS;
    }
}
