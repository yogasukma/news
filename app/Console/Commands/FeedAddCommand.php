<?php

namespace App\Console\Commands;

use App\Models\Feed;
use App\Services\FeedDiscovery;
use App\Services\FeedParser;
use Illuminate\Console\Command;

class FeedAddCommand extends Command
{
    protected $signature = 'rss:feed:add {url : The RSS/Atom feed URL or website URL}';

    protected $description = 'Subscribe to a new RSS/Atom feed (auto-discovers feeds from website URLs)';

    public function handle(FeedParser $parser, FeedDiscovery $discovery): int
    {
        $url = $this->argument('url');

        if (! filter_var($url, FILTER_VALIDATE_URL) || ! Str($url)->startsWith(['http://', 'https://'])) {
            $this->error('Invalid URL. Only http:// and https:// URLs are supported.');

            return self::FAILURE;
        }

        $this->info('Fetching feed...');

        try {
            // Direct feed URL: existing behavior, FeedParser validation is the gate.
            $result = $parser->parse($url);
            $feedUrl = $url;
            $siteUrl = $result['feed']['site_url'];
            $discovered = false;
        } catch (\Exception $parseException) {
            // Not a feed (e.g., an HTML website page): discover the real feed link.
            $this->info('No feed at the URL; scanning page for RSS/Atom links...');

            try {
                $feedUrl = $discovery->discover($url);
                $result = $parser->parse($feedUrl);
                $siteUrl = $url;
                $discovered = true;
            } catch (\Exception $discoveryException) {
                $this->error('Failed to fetch or parse feed: '.$parseException->getMessage().' '.$discoveryException->getMessage());

                return self::FAILURE;
            }
        }

        if (Feed::where('url', $feedUrl)->exists()) {
            $this->error('Already subscribed to this feed URL.');

            return self::FAILURE;
        }

        $feedData = $result['feed'];
        $articleCount = count($result['articles']);

        $feed = Feed::create([
            'title' => $feedData['title'],
            'url' => $feedUrl,
            'site_url' => $siteUrl,
            'description' => $feedData['description'],
        ]);

        if ($discovered) {
            $this->info("Discovered feed '{$feed->title}' at {$feedUrl}.");
        }

        $this->info("Subscribed to '{$feed->title}' (ID: {$feed->id}).");
        $this->info("Found {$articleCount} article(s) in feed.");

        if ($this->confirm('Fetch articles now?', true)) {
            $this->call('rss:fetch', ['feed' => (string) $feed->id]);
        }

        return self::SUCCESS;
    }
}
