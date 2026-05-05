<?php

namespace App\Console\Commands;

use App\Models\Feed;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class FeedHealthCommand extends Command
{
    protected $signature = 'rss:feed:health';

    protected $description = 'Show feed health status — list feeds with errors or disabled feeds';

    public function handle(): int
    {
        $unhealthy = Feed::where('error_count', '>', 0)
            ->orWhere('is_enabled', false)
            ->orderByDesc('error_count')
            ->get();

        if ($unhealthy->isEmpty()) {
            $this->info('All feeds are healthy.');

            return self::SUCCESS;
        }

        $this->warn("Found {$unhealthy->count()} feed(s) with issues:");
        $this->newLine();

        $rows = $unhealthy->map(fn (Feed $feed) => [
            $feed->id,
            $feed->title,
            $feed->is_enabled ? 'Enabled' : '<fg=red>Disabled</>',
            $feed->error_count,
            Str::limit($feed->last_error ?? '-', 60),
        ])->toArray();

        $this->table(
            ['ID', 'Feed', 'Status', 'Errors', 'Last Error'],
            $rows
        );

        $disabled = $unhealthy->filter(fn (Feed $feed) => ! $feed->is_enabled);
        if ($disabled->isNotEmpty()) {
            $this->newLine();
            $this->comment('Re-enable disabled feeds with: php artisan rss:feed:enable {id}');
        }

        return self::SUCCESS;
    }
}
