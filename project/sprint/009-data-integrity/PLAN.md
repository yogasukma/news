# Sprint 009: data-integrity

## Sprint Goal
Reinforce backend data integrity: prevent duplicate articles by URL, auto-reactivate stale disabled feeds, and surface source freshness on a new Sources page.

## Duration
2026-09-10 → 2026-09-10

## Selected Stories
| Story | Title | Points | Priority |
|-------|-------|--------|----------|
| US-038 | Global duplicate prevention by article URL | 5 | P0 |
| US-039 | Re-enable disabled feeds after one month of inactivity | 3 | P1 |
| US-040 | Sources page listing all feeds with last fetch time | 5 | P1 |

## Sprint Capacity
- Total story points: 13
- Number of stories: 3

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

### US-040: Sources page listing all feeds with last fetch time
- [ ] Task 1: Create `app/Http/Controllers/SourcesController.php` with an `index()` action returning all feeds (with folder) ordered by `last_fetched_at` descending, nulls last; support `?fragment=1`
- [ ] Task 2: Add `Route::get('/sources', [SourcesController::class, 'index'])->name('sources')` to `routes/web.php`
- [ ] Task 3: Create `resources/views/sources/index.blade.php` full page using `<x-layouts.app>`
- [ ] Task 4: Create `resources/views/sources/partials/index-content.blade.php` fragment: TOC-style rows (favicon + feed name left, last fetched time right), never-fetched feeds at the bottom
- [ ] Task 5: Add the "Sources" link after the date picker in `articles/partials/index-content.blade.php` with a separator and `data-spa`
- [ ] Task 6: Write Pest feature tests covering: route renders, all feeds listed (not date-scoped), sort order (recent fetch first), never-fetched at bottom, and fragment response
- [ ] Task 7: Run `vendor/bin/pint --dirty --format agent` on modified PHP files