# Sprint Review: Sprint 007 — recent-feeds-fallback

## Sprint Goal
Make the homepage always feel populated by falling back to "Recent Feeds" when today has fewer than 20 articles, with date+time shown on cards in that mode.
**Status**: Achieved

## Stories Delivered

### US-035: Smart homepage — Recent Feeds fallback when today has few articles — DONE
- All 6 acceptance criteria met
- Tests passing: 9/9
- Notes: Controller cleanly falls back to 20 most recent articles when today has < 20; folder filter works in both modes; past dates unaffected

### US-036: Show date+time on article cards in Recent Feeds mode — DONE
- All 4 acceptance criteria met
- Tests passing: 3/3
- Notes: `M j, g:i A` format (e.g., "May 4, 3:45 PM") in recent mode; time-only in today/date mode

## Stories Not Completed
None — all stories delivered.

## Demo Summary
- Visit homepage with ≥20 today articles → shows "Today's Feeds" with time-only cards (unchanged)
- Visit homepage with <20 today articles → shows "Recent Feeds" with up to 20 articles across dates, each card showing date+time
- Date navigation hidden in recent mode; folder filter still works
- Past date pages completely unchanged

## Metrics
- Planned story points: 7
- Delivered story points: 7
- Velocity: 7 points/sprint
