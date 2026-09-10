# Sprint Review: Sprint 009 — data-integrity

## Sprint Goal
Reinforce backend data integrity: prevent duplicate articles by URL and auto-reactivate stale disabled feeds.
**Status**: Achieved

## Stories Delivered

### US-038: Global duplicate prevention by article URL — DONE
- All acceptance criteria met (5/5)
- Tests passing: 11/11 (6 feature + 5 unit)
- Notes:
  - URL is now the single source of truth, checked globally across all feeds; `external_id`/guid no longer drives uniqueness
  - Tracking parameters and fragments are normalized before store & lookup
  - One-time migration normalizes legacy URLs, collapses existing duplicates (keeps oldest), and adds a unique DB index
  - Update-on-match preserves original `feed_id` and `published_at`
  - Blank-link items are skipped (prevents unique-index collisions on feeds with linkless items)

### US-039: Re-enable disabled feeds after one month of inactivity — DONE
- All acceptance criteria met (5/5)
- Tests passing: 5/5
- Notes:
  - New `rss:feed:recover` command re-enables feeds with `is_enabled = false` untouched for 30+ days (`updated_at`), resetting `error_count` and `last_error`
  - CLI output lists each recovered feed with its previous error count
  - Registered on the daily schedule alongside the 4-hour `rss:fetch`

## Stories Not Completed
| Story | Reason | Carry to next sprint? |
|-------|--------|-----------------------|
| US-040 | Excluded at user's request — Sources page deferred | Yes |
| US-041, US-042, US-043 | Modal UX stories not selected for this sprint | Yes |

## Demo Summary
**Backend behavior** (CLI-driven):
1. Run `rss:fetch` twice against the same feed(s) — the second run reports `0 new article(s)`; the same article arriving from a second feed updates the existing row rather than creating a new one. Articles whose permalinks differ only by `?utm_*`/`fbclid`/etc. are recognized as the same article.
2. Run `rss:feed:recover` — disabled feeds that haven't been touched in 30+ days are re-enabled with a summary table ("Recovered N feed(s): Feed | URL | Previous errors"). It runs automatically once a day via the scheduler.

**Verification**: `php artisan test` → 218 passed, 1 pre-existing failure unrelated to this sprint (see Test Report).

## Metrics
- Planned story points: 8
- Delivered story points: 8
- Velocity: 8 points/sprint

## Observations for the Owner
- **Pre-existing test conflict** (not from this sprint): `RecentFeedsFallbackTest` asserts the date picker is hidden in "Recent Feeds" mode, but `main` intentionally keeps it visible (commit `f851227`, matching PRD Module 10). Action recommended: update that test in a future sprint.
- Backlog now holds 4 pending stories (US-040, US-041, US-042, US-043 = 9 points).