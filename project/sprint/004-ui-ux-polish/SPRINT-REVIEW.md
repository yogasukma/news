# Sprint Review: Sprint 004 — UI/UX Polish

## Sprint Goal
Polish the reading experience with feed favicons, better card interactions, and SPA-like navigation that makes date/folder/search switching instant without page reloads.
**Status**: ✅ Achieved

## Stories Delivered

### US-026: Show favicon before feed name — DONE
- All 4 acceptance criteria met
- Tests passing: 10/10
- Notes: Favicon URL stored in DB during `rss:fetch`, falls back to Google's favicon service via accessor, graceful degradation with `onerror` fallback. XSS vulnerability in modal JS found and fixed during code review.

### US-027: Improve article card hover effects — DONE
- All 3 acceptance criteria met
- Tests passing: 5/5
- Notes: Pure CSS change — `hover:shadow-md`, `hover:border-stone-400`, `hover:-translate-y-0.5` with 200ms ease-out transition.

### US-028: SPA-like navigation for dates, folders, and search — DONE
- All 6 acceptance criteria met
- Tests passing: 13/13
- Notes: "HTML over the wire" approach — `?fragment=1` returns partial HTML without layout wrapper. Event delegation handles DOM swaps cleanly. History API for back/forward. Concurrent navigation race condition found and fixed during code review.

## Stories Not Completed
None — all stories delivered.

## Demo Summary

### Favicon display
- Each article card shows the feed's favicon (16×16) before the feed name
- Clicking an article opens the modal — favicon appears next to feed name in the modal header
- Favicons are stored in the database during `rss:fetch`, avoiding runtime external requests
- Feeds without a domain gracefully hide the favicon slot

### Hover effects
- Hovering over an article card produces a visible lift effect (`-translate-y-0.5`), shadow (`shadow-md`), and border darkening (`border-stone-400`)
- Smooth 200ms animation on hover on/off
- Pointer cursor confirms clickability

### SPA navigation
- Clicking date prev/next links updates the article list instantly — no page reload
- Clicking folder filter pills updates the article list instantly
- Submitting the search form (in the header) loads results without a page reload
- The date picker triggers SPA navigation too
- Browser URL updates correctly for each view
- Back/forward buttons restore the previous view
- A thin loading bar appears at the top during navigation
- If JavaScript fails, all links work as normal `<a>` tags (progressive enhancement)

## Metrics
- Planned story points: 12
- Delivered story points: 12
- Velocity: 12 points/sprint
- Tests: 159 total (28 new), all passing
- Code review: 2 critical issues found and fixed (XSS in modal, race condition in SPA)
- Files changed: 18 modified, 6 new
