# Sprint 007: recent-feeds-fallback

## Sprint Goal
Make the homepage always feel populated by falling back to "Recent Feeds" when today has fewer than 20 articles, with date+time shown on cards in that mode.

## Duration
2026-05-05 → 2026-05-05

## Selected Stories
| Story | Title | Points | Priority |
|-------|-------|--------|----------|
| US-035 | Smart homepage — Recent Feeds fallback | 5 | P1 |
| US-036 | Show date+time on article cards in Recent Feeds mode | 2 | P1 |

## Sprint Capacity
- Total story points: 7
- Number of stories: 2

---

## Task Breakdown

### US-035: Smart homepage — Recent Feeds fallback when today has few articles
- [x] Task 1: Update `ArticleController::index()` — add "recent feeds" fallback logic: if today's articles count < 20 and `$date` is today, re-query the 20 most recent articles across all dates, ordered by `published_at DESC`
- [x] Task 2: Pass a `$mode` variable to the view (`'today'` or `'recent'`) so the template knows which mode is active
- [x] Task 3: Handle folder filter in recent mode — when a folder is active, scope the 20-recent query to that folder's feeds
- [x] Task 4: Update `articles.partials.index-content` — change the `<h1>` to show "Recent Feeds" when mode is `'recent'`
- [x] Task 5: Write tests — verify today mode when ≥ 20 articles, recent mode when < 20, folder filter in recent mode, past dates unaffected

### US-036: Show date and time on article cards in Recent Feeds mode
- [x] Task 1: Update the `partials.article-card` component — accept a `$mode` prop and conditionally format the time as date+time (e.g., "May 4, 3:45 PM") when mode is `'recent'`, or time-only (e.g., "3:45 PM") otherwise
- [x] Task 2: Pass `$mode` from `index-content` partial to each `<x-partials.article-card>` call
- [x] Task 3: Write tests — verify time-only format in today mode, date+time format in recent mode, time-only on past date pages
