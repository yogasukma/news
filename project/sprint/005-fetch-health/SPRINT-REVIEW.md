# Sprint Review: Sprint 005 — Fetch Health

## Sprint Goal
Automate feed fetching on a 4-hour schedule, skip undated articles, track fetch errors with auto-disable, and provide CLI tools to manage feed health.
**Status**: ✅ Achieved

## Stories Delivered

### US-029: Schedule automatic feed fetching every 4 hours — DONE
- All 2 acceptance criteria met
- Tests passing: 2/2
- Notes: Changed `routes/console.php` from `hourly()` to `everyFourHours()`. Existing `withoutOverlapping()` prevents concurrent fetches.

### US-030: Skip articles without a publication date — DONE
- All 3 acceptance criteria met
- Tests passing: 4/4
- Notes: `FeedParser::parseDate()` now returns `null` instead of defaulting to `now()` when no date element is present. `FetchFeedsCommand` skips articles with null `published_at` and reports the skipped count in the summary.

### US-031: Track and auto-disable feeds with consecutive fetch errors — DONE
- All 5 acceptance criteria met
- Tests passing: 9/9
- Notes: New migration adds `error_count`, `is_enabled`, and `last_error` to `feeds` table. On failure: increment `error_count`, store error message. On success: reset to 0. At 8 errors → auto-disable (`is_enabled = false`). Disabled feeds skipped by scheduled fetch but remain manually fetchable by ID.

### US-032: CLI command to list and re-enable disabled feeds — DONE
- All 3 acceptance criteria met
- Tests passing: 8/8
- Notes: `rss:feed:health` lists feeds with `error_count > 0` or `is_enabled = false`. `rss:feed:enable {id}` re-enables a feed and clears all error state.

## Stories Not Completed
None — all stories delivered.

## Demo Summary

### Scheduled fetching
- `php artisan schedule:run` triggers `rss:fetch` every 4 hours (`0 */4 * * *`)
- `withoutOverlapping()` prevents multiple fetches running simultaneously
- Only enabled feeds (`is_enabled = true`) are processed automatically

### Skip undated articles
- RSS/Atom items without `<pubDate>`, `<published>`, or `<updated>` are skipped
- Skipped count appears in output: `✓ Blog: 3 new article(s), 2 skipped (no date)`
- Summary table shows total skipped: `Skipped (no date) | 4`

### Error tracking & auto-disable
- On success: feed's `error_count` resets to 0
- On failure: `error_count` increments, `last_error` stores the message
- At 8 errors: feed is automatically disabled with clear output `✗ Dead Feed: ... (errors: 8) [DISABLED]`
- Disabled feeds are skipped in the next scheduled run
- Re-enable with `php artisan rss:feed:enable {id}`

### CLI health management
- `php artisan rss:feed:health` shows all feeds with issues:
  ```
  ID  Feed         Status   Errors  Last Error
  --  ----         ------   ------  ----------
  1   Flaky Feed   Enabled  3       HTTP 500
  2   Dead Feed    Disabled 8       Connection timed out
  ```
- If all healthy: `All feeds are healthy.`

## Metrics
- Planned story points: 14
- Delivered story points: 14
- Velocity: 14 points/sprint
- Tests: 182 total (23 new), all passing
- Code review: 1 warning found and fixed (missing import)
- Files changed: 12 modified, 4 new
- New migration: 1 (3 new columns on `feeds`)
