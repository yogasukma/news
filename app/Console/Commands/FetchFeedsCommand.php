<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Feed;
use App\Services\FeedParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FetchFeedsCommand extends Command
{
    protected $signature = 'rss:fetch {feed? : Specific feed ID to fetch}';

    protected $description = 'Fetch and parse RSS/Atom feeds to discover new articles';

    private int $fetched = 0;

    private int $newArticles = 0;

    private int $errors = 0;

    private int $skipped = 0;

    public function handle(FeedParser $parser): int
    {
        $specificFeed = $this->argument('feed');

        $feeds = $specificFeed
            ? Feed::where('id', (int) $specificFeed)->get()
            : Feed::where('is_enabled', true)->get();

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
                ['Skipped (no date)', $this->skipped],
                ['Errors', $this->errors],
            ]
        );

        return self::SUCCESS;
    }

    private function fetchFeed(Feed $feed, FeedParser $parser): void
    {
        try {
            $result = $parser->parse($feed->url);

            $updates = [];

            // Update feed metadata if title changed
            if ($result['feed']['title'] && $result['feed']['title'] !== $feed->title) {
                $updates['title'] = $result['feed']['title'];
            }

            // Populate favicon_url if missing
            if (empty($feed->favicon_url) && ($result['feed']['site_url'] ?? null)) {
                $domain = parse_url($result['feed']['site_url'], PHP_URL_HOST);
                if ($domain) {
                    $updates['favicon_url'] = 'https://www.google.com/s2/favicons?domain='.$domain.'&sz=32';
                }
            }

            // Clear error state on success
            if ($feed->error_count > 0 || $feed->last_error !== null) {
                $updates['error_count'] = 0;
                $updates['last_error'] = null;
            }

            $updates['last_fetched_at'] = now();

            if ($updates) {
                $feed->update($updates);
            }

            $newForFeed = 0;
            $skippedForFeed = 0;

            foreach ($result['articles'] as $articleData) {
                if ($articleData['published_at'] === null) {
                    $skippedForFeed++;

                    continue;
                }

                $article = $this->storeArticle($feed, $articleData);

                if ($article->wasRecentlyCreated) {
                    $newForFeed++;
                }
            }

            $this->fetched++;
            $this->newArticles += $newForFeed;
            $this->skipped += $skippedForFeed;

            $summary = "  ✓ {$feed->title}: {$newForFeed} new article(s)";
            if ($skippedForFeed > 0) {
                $summary .= ", {$skippedForFeed} skipped (no date)";
            }
            $this->line($summary);
        } catch (\Exception $e) {
            $this->errors++;

            $newErrorCount = $feed->error_count + 1;
            $updates = [
                'error_count' => $newErrorCount,
                'last_error' => Str::limit($e->getMessage(), 255),
                'last_fetched_at' => now(),
            ];

            // Auto-disable after 8 consecutive errors
            if ($newErrorCount >= 8) {
                $updates['is_enabled'] = false;
            }

            $feed->update($updates);

            $disabled = ($newErrorCount >= 8) ? ' [DISABLED]' : '';
            $this->error("  ✗ {$feed->title}: {$e->getMessage()} (errors: {$newErrorCount}){$disabled}");
            Log::warning("Feed fetch failed: {$feed->title}", [
                'feed_id' => $feed->id,
                'url' => $feed->url,
                'error' => $e->getMessage(),
                'error_count' => $newErrorCount,
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
