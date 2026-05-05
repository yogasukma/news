# Sprint 005: Fetch Health

## Sprint Goal
Automate feed fetching on a 4-hour schedule, skip undated articles, track fetch errors with auto-disable, and provide CLI tools to manage feed health.

## Duration
2026-05-05 → TBD

## Selected Stories
| Story | Title | Points | Priority |
|-------|-------|--------|----------|
| US-029 | Schedule automatic feed fetching every 4 hours | 2 | P0 |
| US-030 | Skip articles without a publication date | 2 | P1 |
| US-031 | Track and auto-disable feeds with consecutive fetch errors | 8 | P1 |
| US-032 | CLI command to list and re-enable disabled feeds | 2 | P2 |

## Sprint Capacity
- Total story points: 14
- Number of stories: 4

---

## Task Breakdown

### US-029: Schedule automatic feed fetching every 4 hours
- [x] Task 1: Update `routes/console.php` — `rss:fetch` every 4 hours
- [x] Task 2: Updated existing test to verify `everyFourHours` schedule

### US-030: Skip articles without a publication date
- [x] Task 1: Update `FeedParser::parseDate()` — return `null` when no date element exists
- [x] Task 2: Update `FetchFeedsCommand::fetchFeed()` — skip articles where `published_at` is null
- [x] Task 3: Add skipped count tracking and display in fetch summary table
- [x] Task 4: Updated existing tests to reflect new behavior

### US-031: Track and auto-disable feeds with consecutive fetch errors
- [x] Task 1: Migration — `error_count` (integer, default 0), `is_enabled` (boolean, default true), `last_error` (string, nullable) on `feeds` table
- [x] Task 2: Update `Feed` model — new fillable fields and casts
- [x] Task 3: Update `FetchFeedsCommand::fetchFeed()` — on success: reset error_count to 0; on failure: increment error_count, store last_error
- [x] Task 4: Auto-disable logic — when error_count reaches 8, set `is_enabled = false`
- [x] Task 5: Update `FetchFeedsCommand::handle()` — only fetch feeds where `is_enabled = true` (when no specific feed ID given)

### US-032: CLI command to list and re-enable disabled feeds
- [x] Task 1: Create `rss:feed:health` command — list all feeds with error_count > 0 or is_enabled = false
- [x] Task 2: Create `rss:feed:enable {feed}` command — re-enable a disabled feed (is_enabled = true, error_count = 0, last_error = null)
