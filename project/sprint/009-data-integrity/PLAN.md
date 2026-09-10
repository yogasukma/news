# Sprint 009: data-integrity

## Sprint Goal
Reinforce backend data integrity: prevent duplicate articles by URL and auto-reactivate stale disabled feeds.

## Duration
2026-09-10 → 2026-09-10

## Selected Stories
| Story | Title | Points | Priority |
|-------|-------|--------|----------|
| US-038 | Global duplicate prevention by article URL | 5 | P0 |
| US-039 | Re-enable disabled feeds after one month of inactivity | 3 | P1 |

## Sprint Capacity
- Total story points: 8
- Number of stories: 2

---

## Task Breakdown

### US-038: Global duplicate prevention by article URL
- [ ] Task 1: Add a URL normalization helper that strips common tracking parameters (utm_*, fbclid, gclid, mc_cid, etc.) from article URLs
- [ ] Task 2: Refactor `FetchFeedsCommand::storeArticle()` to look up existing articles by normalized URL globally (across ALL feeds) instead of per-feed external_id
- [ ] Task 3: When a URL match exists, update mutable fields (title, content, author, cover_image) on the existing record — never change feed_id or published_at
- [ ] Task 4: Keep storing `external_id` on create; ensure missing/changed guids still deduplicate via the URL check
- [ ] Task 5: Write Pest tests covering: same-feed duplicate, cross-feed duplicate, brand-new URL, tracking-parameter variants, and changed/missing guid
- [ ] Task 6: Run `vendor/bin/pint --dirty --format agent` on modified PHP files

### US-039: Re-enable disabled feeds after one month of inactivity
- [ ] Task 1: Create `app/Console/Commands/FeedRecoverCommand.php` with signature `rss:feed:recover`
- [ ] Task 2: Query feeds with `is_enabled = false` and `updated_at` older than 1 month; re-enable them (`is_enabled = true`, `error_count = 0`, clear `last_error`)
- [ ] Task 3: Output a summary table of recovered feeds (name, previous error count) and the total count
- [ ] Task 4: Register `rss:feed:recover` in `routes/console.php` scheduler to run daily
- [ ] Task 5: Write Pest tests covering: stale disabled feed recovered, recent disabled feed untouched, enabled feed untouched, and summary output
- [ ] Task 6: Run `vendor/bin/pint --dirty --format agent` on modified PHP files