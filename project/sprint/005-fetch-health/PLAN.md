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
- [ ] Task 1: Register scheduled command in `routes/console.php` — `rss:fetch` every 4 hours
- [ ] Task 2: Write test verifying the schedule is registered

### US-030: Skip articles without a publication date
- [ ] Task 1: Update `FetchFeedsCommand::storeArticle()` — skip if `published_at` is null or was defaulted to `now()` by FeedParser
- [ ] Task 2: Update `FeedParser` — return `null` for `published_at` when no date element exists (instead of defaulting to `now()`)
- [ ] Task 3: Add skipped count tracking and display in fetch summary
- [ ] Task 4: Write tests for skipping undated articles

### US-031: Track and auto-disable feeds with consecutive fetch errors
- [ ] Task 1: Add migration — `error_count` (integer, default 0), `is_enabled` (boolean, default true), `last_error` (string, nullable) on `feeds` table
- [ ] Task 2: Update `Feed` model — add new fillable fields and casts
- [ ] Task 3: Update `FetchFeedsCommand::fetchFeed()` — on success: reset error_count to 0; on failure: increment error_count, store last_error
- [ ] Task 4: Auto-disable logic — when error_count reaches 8, set `is_enabled = false`
- [ ] Task 5: Update `FetchFeedsCommand::handle()` — only fetch feeds where `is_enabled = true`
- [ ] Task 6: Write tests for error tracking, auto-disable, and skipping disabled feeds

### US-032: CLI command to list and re-enable disabled feeds
- [ ] Task 1: Create `rss:feed:health` command — list all feeds with error_count > 0, highlight disabled ones
- [ ] Task 2: Create `rss:feed:enable {feed}` command — re-enable a disabled feed (is_enabled = true, error_count = 0)
- [ ] Task 3: Write tests for both commands
