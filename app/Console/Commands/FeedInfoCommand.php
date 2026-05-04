<?php

namespace App\Console\Commands;

use App\Models\Feed;
use Illuminate\Console\Command;

class FeedInfoCommand extends Command
{
    protected $signature = 'rss:feed:info {feed : The feed ID}';

    protected $description = 'Show detailed information about a feed';

    public function handle(): int
    {
        $feed = Feed::with('folder')->withCount('articles')->find((int) $this->argument('feed'));

        if ($feed === null) {
            $this->error('Feed not found.');

            return self::FAILURE;
        }

        $siteUrl = $feed->site_url ?? '-';
        $folder = $feed->folder?->name ?? 'Uncategorized';
        $lastFetched = $feed->last_fetched_at?->toDateTimeString() ?? 'Never';

        $this->info("Title:         {$feed->title}");
        $this->info("ID:            {$feed->id}");
        $this->info("URL:           {$feed->url}");
        $this->info("Site URL:      {$siteUrl}");
        $this->info("Folder:        {$folder}");
        $this->info("Articles:      {$feed->articles_count}");
        $this->info("Last Fetched:  {$lastFetched}");
        $this->info("Created:       {$feed->created_at->toDateTimeString()}");

        if ($feed->description) {
            $this->info("Description:   {$feed->description}");
        }

        return self::SUCCESS;
    }
}
