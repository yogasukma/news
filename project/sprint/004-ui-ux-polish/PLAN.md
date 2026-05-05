# Sprint 004: UI/UX Polish

## Sprint Goal
Polish the reading experience with feed favicons, better card interactions, and SPA-like navigation that makes date/folder/search switching instant without page reloads.

## Duration
2026-05-04 → TBD

## Selected Stories
| Story | Title | Points | Priority |
|-------|-------|--------|----------|
| US-026 | Show favicon before feed name | 3 | P1 |
| US-027 | Improve article card hover effects | 1 | P1 |
| US-028 | SPA-like navigation for dates, folders, and search | 8 | P1 |

## Sprint Capacity
- Total story points: 12
- Number of stories: 3

---

## Task Breakdown

### US-026: Show favicon before feed name
- [x] Task 1: Add `getFaviconUrlAttribute()` to Feed model — uses stored `favicon_url`, falls back to Google's favicon service from `site_url` domain
- [x] Task 2: Populate `favicon_url` during `rss:fetch` if empty (FetchFeedsCommand)
- [x] Task 3: Update article-card partial to show favicon `<img>` before feed name
- [x] Task 4: Update modal JS to show favicon in modal header meta
- [x] Task 5: Handle missing favicon with `onerror="this.style.display='none'"`
- [x] Task 6: Add `favicon_url` to ArticleController JSON response

### US-027: Improve article card hover effects
- [x] Task 1: Update article-card Tailwind classes — `hover:shadow-md`, `hover:border-stone-400`, `hover:-translate-y-0.5`, `transition-all duration-200 ease-out`
- [x] Task 2: Ensure cursor-pointer is already set (was already present)
- [x] Task 3: Smooth transition timing (200ms ease-out)

### US-028: SPA-like navigation for dates, folders, and search
- [x] Task 1: Add `?fragment=1` support to `index()` and `search()` — return partial Blade content (no layout wrapper)
- [x] Task 2: Create `resources/js/spa.js` — intercept clicks on `[data-spa]` links, `[data-spa-search]` form submissions, `[data-spa-date]` date picker changes
- [x] Task 3: Fetch URL with `?fragment=1`, swap `<main>` innerHTML with response
- [x] Task 4: Update browser URL via `history.pushState()` on each navigation
- [x] Task 5: Handle `popstate` event for browser back/forward
- [x] Task 6: Add a subtle loading bar at the top of the page during fetch
- [x] Task 7: Handle fetch errors gracefully (fall back to normal navigation)
- [x] Task 8: Use event delegation on `document` (no re-binding needed after DOM swap)
- [x] Task 9: Update `vite.config.js` to include `spa.js` in the build
- [x] Task 10: Extract `index-content.blade.php` and `search-content.blade.php` partials; add `data-spa` attributes to all navigable links
