# Code Review: Sprint 009

## Summary
- Files reviewed: 10 (2 app files, 1 model, 1 migration, 1 route file, 5 test files)
- Issues found: 3 (Critical: 0, Warning: 2, Info: 1)
- Issues fixed: 3
- Overall Assessment: **Pass** (after fixes)

## Review Results

### File: `app/Models/Article.php`
- **[OK]** `normalizeUrl()` returns input unchanged for unparseable URLs (defensive)
- **[OK]** Strips only known tracking params (`utm_*`, `fbclid`, `gclid`, `mc_cid`, `mc_eid`, `ref`, `source`) — `http_build_query` re-encodes the remainder safely
- **[OK]** Fragment stripping is correct — fragments are never part of a permalink

### File: `app/Console/Commands/FetchFeedsCommand.php`
- **[Warning]** Blank-item URL regression: a feed item without `<link>` yields `''`; a global unique index on `url` would then collide across feeds and falsely error a healthy feed → **Fixed**: blank-URL articles are now skipped (same counter as "no date"), covered by a new test
- **[OK]** Update-on-match preserves `feed_id` and `published_at`
- **[OK]** `external_id` is only backfilled when the existing row lacks one — a changed/missing guid never clobbers the original
- **[OK]** Normalized URL used for both lookup and storage — idempotent

### File: `database/migrations/2026_09_10_123645_add_unique_url_to_articles_table.php`
- **[Warning]** Pre-existing raw URLs (with `utm_*` params) would not match normalized runtime lookups → re-duplication risk on real data → **Fixed**: migration now normalizes all existing rows first, then de-duplicates (keeps MIN(id) per URL), then adds the unique index
- **[OK]** Query-builder subquery dedupe avoids `NOT IN (NULL)` semantics (MIN(id) is never NULL)
- **[OK]** `down()` drops only the index (irreversible DML is intentionally one-way)

### File: `app/Console/Commands/FeedRecoverCommand.php`
- **[Info]** Used `subMonth()`; PRD specifies "30+ days" → **Fixed** to `subDays(30)`
- **[OK]** Scoped strictly to `is_enabled = false`; enabled feeds with errors are never touched
- **[OK]** Recovery resets `is_enabled`, `error_count`, `last_error` — full reset, no stale state

### File: `routes/console.php`
- **[OK]** `rss:feed:recover` registered `daily()` alongside `rss:fetch` (both deterministic)

### File: `tests/Feature/FetchDedupTest.php`
- **[OK]** 7 tests covering all 5 US-038 acceptance criteria + cross-feed + blank-URL guard
- **[OK]** Uses `Http::fakeSequence()` where two fetches hit the same URL pattern (repeated `Http::fake()` calls stack — a real pitfall that was caught and fixed)
- **[OK]** XML properly escapes `&` (`&amp;`) in tracking-param URLs

### File: `tests/Feature/FeedRecoverCommandTest.php`
- **[OK]** 5 tests covering all US-039 acceptance criteria incl. scheduler registration (expression `0 0 * * *`)

### File: `tests/Unit/ArticleNormalizeUrlTest.php`
- **[OK]** 5 unit tests: utm stripping, known params, fragment, port/path preservation, unparseable input

## Security Review (OWASP-oriented)
- **[OK]** Injection: no raw SQL with user input; query-builder/Eloquent throughout; migration uses parameterized subquery
- **[OK]** XSS: no new output surface; command output is CLI-only; feed titles rendered via Blade escaping in existing views
- **[OK]** Secrets: none introduced; no hardcoded credentials
- **[OK]** Input validation: feed data is the only input to `normalizeUrl()` — parse failures safely return the original string

## Pre-existing issue observed (outside this sprint's scope)
- `RecentFeedsFallbackTest` → "hides date navigation in recent mode" fails on `main` too: the date picker was intentionally kept visible in recent mode by commit `f851227` ("show datepicker in recent feeds"), which contradicts the older test assertion. Not caused by this sprint; flagged for the owner.

## Overall Assessment
**Pass** — all issues found were fixed with regression tests added.