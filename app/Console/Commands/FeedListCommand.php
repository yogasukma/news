<?php

namespace App\Console\Commands;

use App\Models\Feed;
use Illuminate\Console\Command;

class FeedListCommand extends Command
{
    protected $signature = 'rss:feed:list';

    protected $description = 'List all subscribed feeds';

    public function handle(): int
    {
        $feeds = Feed::with('folder')->withCount('articles')->orderBy('title')->get();

        if ($feeds->isEmpty()) {
            $this->info('No feeds found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Title', 'URL', 'Folder', 'Articles', 'Last Fetched'],
            $feeds->map(fn (Feed $feed) => [
                $feed->id,
                Str($feed->title)->limit(40),
                Str($feed->url)->limit(50),
                $feed->folder?->name ?? '-',
                $feed->articles_count,
                $feed->last_fetched_at?->diffForHumans() ?? 'Never',
            ])
        );

        return self::SUCCESS;
    }
}
