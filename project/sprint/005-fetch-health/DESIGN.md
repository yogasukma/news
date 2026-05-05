# Technical Design: Sprint 005 — Fetch Health

## 1. Architecture Overview

Four changes to the feed fetching pipeline:

1. **Scheduler** — Update `routes/console.php` from `hourly()` to `everyFourHours()`
2. **Skip undated articles** — `FeedParser::parseDate()` returns `null` instead of defaulting to `now()`; `FetchFeedsCommand::storeArticle()` skips articles with null `published_at`
3. **Error tracking** — New columns on `feeds` table; `FetchFeedsCommand::fetchFeed()` tracks success/failure, auto-disables at 8 consecutive errors
4. **CLI health commands** — New `rss:feed:health` and `rss:feed:enable` commands

## 2. Technology Stack

- Laravel scheduler (already in use, just changing frequency)
- New migration for `feeds` table columns
- Two new Artisan commands
- No new dependencies

## 3. Data Model

### Migration: Add health columns to `feeds` table

| Column | Type | Default | Description |
|--------|------|---------|-------------|
| `error_count` | `unsigned integer` | `0` | Consecutive fetch errors |
| `is_enabled` | `boolean` | `true` | Whether feed is active |
| `last_error` | `string` (nullable) | `null` | Last error message |

### Feed Model Updates

- Add `error_count`, `is_enabled`, `last_error` to `$fillable`
- Add `is_enabled` cast to `boolean`

### FeedParser Changes

- `parseDate()` return type changes from `string` to `?string`
- Returns `null` when no date element exists (was: `now()->toIso8601String()`)
- Both `parseRssItem()` and `parseAtomEntry()` pass through the null value
- Callers of `parseDate()` already handle strings — `storeArticle()` now checks for null and skips

## 4. API Design

No new HTTP routes. All changes are CLI-only.

### CLI Commands

**Existing — `rss:fetch {feed?}`**
- Modified: filters `is_enabled = true` for scheduled fetches (but allows fetching specific disabled feeds by ID for manual testing)
- Modified: skips articles where `published_at` is null
- Modified: tracks `skipped` count in summary
- Modified: resets `error_count` on success, increments on failure
- Modified: auto-disables feed when `error_count` reaches 8

**New — `rss:feed:health`**
- Lists all feeds with `error_count > 0` or `is_enabled = false`
- Shows: title, URL, error_count, is_enabled status, last_error message
- Shows "All feeds are healthy" if no issues

**New — `rss:feed:enable {feed}`**
- Accepts feed ID
- Sets `is_enabled = true`, `error_count = 0`, `last_error = null`
- Confirms re-enable with feed title

## 5. File Structure

```
database/migrations/
└── 2026_05_05_*_add_health_columns_to_feeds_table.php  ← NEW

app/
├── Models/Feed.php              ← Modified: new fillable + casts
├── Services/FeedParser.php      ← Modified: parseDate returns ?string
└── Console/Commands/
    ├── FetchFeedsCommand.php    ← Modified: error tracking, skip undated
    ├── FeedHealthCommand.php    ← NEW
    └── FeedEnableCommand.php    ← NEW

routes/console.php               ← Modified: everyFourHours()

tests/Feature/
├── FetchFeedsCommandTest.php    ← Modified: new tests
├── FeedHealthCommandTest.php    ← NEW
└── FeedEnableCommandTest.php    ← NEW
```

## 6. Design Decisions

### Decision 1: `parseDate()` returns null instead of defaulting to `now()`
**Choice**: Return null, skip the article in `storeArticle()`.
**Reason**: The user explicitly requested skipping undated articles. Defaulting to `now()` would silently give incorrect dates. Returning null makes the intent explicit and lets the caller decide what to do.

### Decision 2: Auto-disable threshold of 8 consecutive errors
**Choice**: Hard-coded threshold of 8 in `FetchFeedsCommand`.
**Reason**: At one fetch every 4 hours, 8 errors = ~32 hours of continuous failure. That's a strong signal the feed is permanently broken. Not configurable — keeps it simple (YAGNI).

### Decision 3: Manual `rss:fetch {feed}` can still fetch disabled feeds
**Choice**: When a specific feed ID is passed, ignore `is_enabled`.
**Reason**: The owner may want to manually test a disabled feed before re-enabling it. The `is_enabled` filter only applies when fetching all feeds (scheduled or `rss:fetch` without arguments).

### Decision 4: `last_error` stores the exception message
**Choice**: Store the exception message string from the failed fetch.
**Reason**: Useful for diagnosis in `rss:feed:health`. Truncated to 255 chars (column is a string, not text) — exception messages are typically short.

## 7. Security Considerations

- Error messages stored in `last_error` come from caught exceptions (HTTP errors, XML parse errors) — no user input
- `rss:feed:enable` command is CLI-only, same security boundary as all other feed management commands
- Migration adds columns with safe defaults (`0`, `true`, `null`) — no data loss risk

## 8. Risks & Mitigations

| Risk | Mitigation |
|------|-----------|
| Existing articles with `published_at` set to `now()` from the old parser behavior | No impact — those are already saved. Only new articles going forward are affected |
| `parseDate()` return type change breaks callers | PHP return types are not enforced at runtime for `?string` vs `string` — but we'll update the PHPDoc and all callers |
| Feed transiently fails once, gets error_count=1, then succeeds | error_count resets to 0 on any success — one-off failures are self-healing |
