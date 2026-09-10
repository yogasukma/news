# Retrospective: Sprint 009 — data-integrity

## What went well
- **Scope discipline**: The user trimmed US-040 mid-sprint; unchanged code stayed untouched and the backlog cleanly preserved the deferred stories. Scope changes were handled in seconds.
- **Adversarial review caught real issues**: The migration-normalization gap and the blank-URL unique-index collision were both genuine data-integrity bugs that would have shipped — both were root-caused and fixed with regression tests on the spot.
- **Test-first verification**: 16 new tests for 2 stories with 100% acceptance-criteria coverage; existing 202-test suite stayed green (1 pre-existing failure proven unrelated by reproducing it on `main`).
- **The Http::fake pitfall surfaced early**: two fetches hitting the same URL pattern stacked stubs and returned stale responses — caught via failing tests and fixed with `Http::fakeSequence()`.

## What could be improved
- **Legacy-data thinking should start at design time**: the "existing rows won't match normalized lookups" issue was only caught in code review, not design. Future migrations touching normalization/indexing should explicitly plan for existing data in Phase 4.
- **Configuration drift**: a pre-existing test (`RecentFeedsFallbackTest`) contradicts shipped behavior and has been failing on `main` since May. We flagged it, but the team should schedule a tiny cleanup sprint to align the test with the PRD (Module 10).

## Action items for next sprint
1. Fix/align `RecentFeedsFallbackTest` "hides date navigation in recent mode" with PRD Module 10 (or update the PRD if hiding is preferred).
2. Consider a `config('rss.dedup_tracking_params')` list if more analytics params are discovered in the wild.
3. Pick up US-040 (Sources page) + modal polish US-041–043 — 9 points total, one sprint's worth.