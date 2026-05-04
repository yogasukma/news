<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Feed;
use App\Services\FeedParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchFeedsCommand extends Command
{
    protected $signature = 'rss:fetch {feed? : Specific feed ID to fetch}';

    protected $description = 'Fetch and parse RSS/Atom feeds to discover new articles';

    private int $fetched = 0;

    private int $newArticles = 0;

    private int $errors = 0;

    public function handle(FeedParser $parser): int
    {
        $feeds = $this->argument('feed')
            ? Feed::where('id', (int) $this->argument('feed'))->get()
            : Feed::all();

        if ($feeds->isEmpty()) {
            $this->info('No feeds to fetch.');

            return self::SUCCESS;
        }

        if ($feeds->count() === 1) {
            $this->info("Fetching '{$feeds->first()->title}'...");
        } else {
            $this->info("Fetching {$feeds->count()} feed(s)...");
        }

        foreach ($feeds as $feed) {
            $this->fetchFeed($feed, $parser);
        }

        $this->newLine();
        $this->info('Fetch complete.');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Feeds fetched', $this->fetched],
                ['New articles', $this->newArticles],
                ['Errors', $this->errors],
            ]
        );

        return self::SUCCESS;
    }

    private function fetchFeed(Feed $feed, FeedParser $parser): void
    {
        try {
            $result = $parser->parse($feed->url);

            // Update feed metadata if title changed
            if ($result['feed']['title'] && $result['feed']['title'] !== $feed->title) {
                $feed->update(['title' => $result['feed']['title']]);
            }

            $newForFeed = 0;

            foreach ($result['articles'] as $articleData) {
                $article = $this->storeArticle($feed, $articleData);

                if ($article->wasRecentlyCreated) {
                    $newForFeed++;
                }
            }

            $feed->update(['last_fetched_at' => now()]);

            $this->fetched++;
            $this->newArticles += $newForFeed;

            $this->line("  ✓ {$feed->title}: {$newForFeed} new article(s)");
        } catch (\Exception $e) {
            $this->errors++;
            $this->error("  ✗ {$feed->title}: {$e->getMessage()}");
            Log::warning("Feed fetch failed: {$feed->title}", [
                'feed_id' => $feed->id,
                'url' => $feed->url,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function storeArticle(Feed $feed, array $data): Article
    {
        $identifier = $data['external_id'] ?? $data['url'] ?? null;

        $matchFields = $identifier !== null
            ? ['feed_id' => $feed->id, 'external_id' => $identifier]
            : ['feed_id' => $feed->id, 'url' => $data['url']];

        $article = Article::where($matchFields)->first();

        if ($article !== null) {
            // Update mutable fields only — preserve published_at
            $article->update([
                'title' => $data['title'],
                'url' => $data['url'],
                'content' => $data['content'],
                'author' => $data['author'],
                'cover_image' => $data['cover_image'],
            ]);

            return $article;
        }

        return Article::create([
            'feed_id' => $feed->id,
            'title' => $data['title'],
            'url' => $data['url'],
            'content' => $data['content'],
            'author' => $data['author'],
            'published_at' => $data['published_at'],
            'cover_image' => $data['cover_image'],
            'external_id' => $data['external_id'],
        ]);
    }
}
