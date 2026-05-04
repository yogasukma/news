<?php

namespace App\Console\Commands;

use App\Models\Feed;
use App\Services\FeedParser;
use Illuminate\Console\Command;

class FeedAddCommand extends Command
{
    protected $signature = 'rss:feed:add {url : The RSS/Atom feed URL}';

    protected $description = 'Subscribe to a new RSS/Atom feed';

    public function handle(FeedParser $parser): int
    {
        $url = $this->argument('url');

        if (! filter_var($url, FILTER_VALIDATE_URL) || ! Str($url)->startsWith(['http://', 'https://'])) {
            $this->error('Invalid URL. Only http:// and https:// URLs are supported.');

            return self::FAILURE;
        }

        if (Feed::where('url', $url)->exists()) {
            $this->error('Already subscribed to this feed URL.');

            return self::FAILURE;
        }

        $this->info('Fetching feed...');

        try {
            $result = $parser->parse($url);
        } catch (\Exception $e) {
            $this->error("Failed to fetch or parse feed: {$e->getMessage()}");

            return self::FAILURE;
        }

        $feedData = $result['feed'];
        $articleCount = count($result['articles']);

        $feed = Feed::create([
            'title' => $feedData['title'],
            'url' => $url,
            'site_url' => $feedData['site_url'],
            'description' => $feedData['description'],
        ]);

        $this->info("Subscribed to '{$feed->title}' (ID: {$feed->id}).");
        $this->info("Found {$articleCount} article(s) in feed.");

        if ($this->confirm('Fetch articles now?', true)) {
            $this->call('rss:fetch', ['feed' => (string) $feed->id]);
        }

        return self::SUCCESS;
    }
}
