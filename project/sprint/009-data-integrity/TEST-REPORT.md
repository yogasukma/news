# Test Report: Sprint 009

## Summary
- Total tests: 219
- Passed: 218
- Failed: 1 (pre-existing — see below)
- Skipped: 0
- Coverage: N/A (no coverage driver configured)

The 1 failure is **pre-existing and unrelated** to this sprint: `RecentFeedsFallbackTest → "hides date navigation in recent mode"` fails identically on `main`. It asserts the date picker is absent in recent mode, but commit `f851227` ("show datepicker in recent feeds") intentionally kept it visible — the test contradicts the implemented (and PRD-specified) behavior. Flagged for the product owner.

## Results by User Story

### US-038: Global duplicate prevention by article URL
| Test | Description | Result |
|------|-------------|--------|
| test_fetch_dedup_1 | Same-feed duplicate not stored twice; second fetch reports 0 new | PASS |
| test_fetch_dedup_2 | Mutable fields (title/content) updated on existing article; published_at preserved | PASS |
| test_fetch_dedup_3 | Same URL arriving from a second feed updates, never duplicates; feed_id preserved | PASS |
| test_fetch_dedup_4 | URLs differing only by tracking params treated as identical; stored URL normalized | PASS |
| test_fetch_dedup_5 | Guid changed/disappeared — still deduplicated by URL; external_id preserved | PASS |
| test_fetch_dedup_6 | Blank-URL items skipped (no unique-index collision across feeds) | PASS |
| test_normalize_url_1 | utm_* params stripped, other params kept | PASS |
| test_normalize_url_2 | fbclid/gclid/mc_cid/mc_eid/ref/source stripped | PASS |
| test_normalize_url_3 | Fragment stripped | PASS |
| test_normalize_url_4 | Port + path + remaining query preserved | PASS |
| test_normalize_url_5 | Unparseable input returned unchanged | PASS |

### US-039: Re-enable disabled feeds after one month of inactivity
| Test | Description | Result |
|------|-------------|--------|
| test_recover_1 | No stale feeds → "No feeds to recover" | PASS |
| test_recover_2 | Disabled > 30 days → re-enabled, error_count=0, last_error cleared, summary shown | PASS |
| test_recover_3 | Disabled < 30 days → left untouched | PASS |
| test_recover_4 | Enabled feed with errors → left untouched | PASS |
| test_recover_5 | Command registered on the daily schedule (`0 0 * * *`) | PASS |

## Acceptance Criteria Coverage
| Story | Criterion | Test | Status |
|-------|-----------|------|--------|
| US-038 | AC1: same-feed URL match updates, no duplicate | fetch_dedup_1, fetch_dedup_2 | PASS |
| US-038 | AC2: cross-feed URL match updates, no duplicate | fetch_dedup_3 | PASS |
| US-038 | AC3: brand-new URL creates article | fetch_dedup_3 (first fetch) | PASS |
| US-038 | AC4: tracking-param variance treated as same URL | fetch_dedup_4 | PASS |
| US-038 | AC5: missing/changed guid still deduplicates | fetch_dedup_5 | PASS |
| US-039 | AC1: stale disabled feed recovered + reset | recover_2 | PASS |
| US-039 | AC2: recent disabled feed remains disabled | recover_3 | PASS |
| US-039 | AC3: enabled feed untouched | recover_4 | PASS |
| US-039 | AC4: summary lists recovered feeds + count | recover_2 | PASS |
| US-039 | AC5: scheduled daily | recover_5 | PASS |

## Regression Verification
- `FetchFeedsCommandTest` (7 tests) — dedup refactor introduced no regressions: PASS
- `FetchHealthTest` / `FetchSingleFeedTest` (15 tests) — fetch error handling, schedule, skip-without-date unchanged: PASS
- `FeedHealthCommandTest`, `FeedEnableCommandTest`, `FeedAddCommandTest`, folders/OPML/UI suites: PASS

## Failed Tests (if any)
- `RecentFeedsFallbackTest → "hides date navigation in recent mode"` — pre-existing on `main` (commit `f851227` describes the intended behavior the test contradicts). **No fix applied in this sprint** (outside scope); recommend aligning the test or PRD in a future sprint.