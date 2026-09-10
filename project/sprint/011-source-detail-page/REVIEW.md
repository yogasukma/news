# Code Review: Sprint 011

## Summary
- Files reviewed: 10 (1 controller, 1 route, 2 view partials + 1 layout wrapper, 1 shared card component, 1 JS module, 3 test files)
- Issues found: 1 (Critical: 0, Warning: 0, Info: 1)
- Issues fixed: 1 (over-broad JS assertion in US-046 test, fixed during development)
- Review rounds: 2 (initial pass + follow-up review of US-047/US-048 additions)

## Review Results

### File: app/Http/Controllers/SourcesController.php
- **[OK]** `show()` mirrors `index()` fragment contract exactly; implicit route-model binding returns 404 for unknown ids
- **[OK]** `paginate(30)` + `orderByDesc('published_at')` — no raw SQL, no injection surface; eager loads `feed.folder` (needed by shared article-card)
- **[Info]** `with('feed.folder')` is slightly redundant since the feed is already bound — harmless, consistent with ArticleController's pattern

### File: routes/web.php
- **[OK]** `/sources/{feed}` registered AFTER literal `/sources` — exact match wins; confirmed via `php artisan route:list` (both routes present)

### File: resources/views/sources/partials/index-content.blade.php
- **[OK]** Rows are now unconditional `data-spa` links to `route('sources.show', $feed)` — null-`site_url` feeds remain clickable (old plain-text branch removed)
- **[OK]** Last-fetched time kept outside the link — TOC layout + US-040 "time outside link" contract preserved
- **[OK]** Old `title="{{ $siteUrl }}"` new-tab attribute removed; no `target="_blank"` remains on the row

### File: resources/views/sources/partials/show-content.blade.php
- **[OK]** Header: favicon (lazy + onerror hide), escaped title, article count
- **[OK]** External site link guarded by `str_starts_with($feed->site_url, 'http')`, escaped by `{{ }}`, `target="_blank" rel="noopener noreferrer"` (tab-nabbing safe), NO `data-spa` (verified — spa.js only intercepts `data-spa` anchors)
- **[OK]** Cards use `mode='recent'` → date+time per US-037 precedent; modal wiring from shared component
- **[OK]** Pagination uses `hasPages()`/`onFirstPage()` guards; links carry `data-spa` (spa.js re-appends `fragment=1`)
- **[Info]** Pagination block duplicates `search-content.blade.php` — accepted project convention; future candidate for a shared Blade component

### File: resources/views/sources/show.blade.php
- **[OK]** Matches the established `index.blade.php` wrapper shape (`<x-layouts.app>` + include)

### File: tests/Feature/SourceDetailPageTest.php (new)
- **[OK]** 14 tests covering 200/404, header contents, external-link guards (valid + missing site_url), back link, fragment, newest-first, feed scoping, pagination (35 articles → page 2 has 5), date+time format (computed from the same Carbon instance), modal `openArticle(id)` wiring, empty state, article count

### File: tests/Feature/SourcesPageTest.php (modified)
- **[OK]** "new-tab external link" test rewritten to assert internal detail link + `data-spa` + no external href; "plain text" test rewritten to assert clickable detail link for null-`site_url` feeds

## Issues Fixed During Development
1. Over-broad `assertDontSee('target="_blank"')` on the full sources page — the shared modal layout legitimately contains the "Read original" new-tab link. Retargeted to the row link specifically (`route('sources.show', $feed).'" target="_blank"'` absence).
2. Over-broad `not->toContain('href="http')` on the detail fragment — the `route('sources')` back link renders as absolute `http://localhost/sources`. Retargeted to assert absence of the external-link contract markers (`target="_blank"` / `rel="noopener noreferrer"`) in the fragment.

## OWASP / Security Checks
- **Injection**: No user input reaches SQL (bound `feed_id` via relationship; constants only) ✅
- **XSS**: All dynamic output escaped via Blade `{{ }}` (title, site_url, favicon_url) ✅
- **AuthN/Z**: Public read-only model preserved; no new state-changing routes ✅
- **Tab-nabbing**: `rel="noopener noreferrer"` on all new-tab links ✅
- **Secrets**: None introduced ✅
- **Sensitive data**: No user data exposed beyond public article content (pre-existing exposure model) ✅

## Overall Assessment
**Pass** — no critical or warning issues.

## Follow-up Review (US-047 / US-048 additions)

### File: resources/views/sources/partials/show-content.blade.php (US-047)
- **[OK]** External-link SVG icon added inside the site-URL anchor (icon + URL one anchor, AC). `target="_blank"` + `rel="noopener noreferrer"` preserved; no `data-spa` (external link stays default behavior)

### File: resources/views/components/partials/article-card.blade.php (US-048)
- **[OK]** Feed name wrapped in `<a href="{{ route('sources.show', $article->feed) }}" data-spa>` with hover underline — SPA navigation works via existing spa.js; feed relationship is eager-loaded on all consuming pages (home/date/search/source-detail)
- **[OK]** Modal-open handlers guarded: `onclick` / `onkeydown` now bail when `event.target.closest('a')` — clicking the feed link (or any future inner link) never opens the modal; no `stopPropagation()` used, so spa.js's document-level interception still fires
- **[OK]** Favicon stays inside the link (hover affordance covers icon+title); folder name/separator remain outside

### File: resources/js/app.js (US-048)
- **[OK]** Modal meta feed name built via DOM API (`createElement` + `createTextNode`) — XSS-safe, no `innerHTML` with feed data
- **[OK]** Link carries `data-spa` + `href="/sources/{id}"` and a `closeModal` click listener — click closes the modal (target-phase listener) before spa.js intercepts navigation (bubble-phase); no double navigation
- **[OK]** Author/date text nodes preserved verbatim

### Tests (US-047 / US-048)
- **[OK]** `SourceDetailPageTest`: icon-in-anchor position assertions; card feed-link + modal-guard feature assertions; app.js source assertions for the modal feed link (`/sources/${id}`, `data-spa`, `closeModal`, textNode)

### Issues fixed in follow-up round
1. US-046 "wires article cards to open in the reading modal" asserted the old exact `onclick="openArticle(id)"` — updated to assert the guarded handler (`openArticle(...)` + `if (!event.target.closest('a')) openArticle`), reflecting the new contract.

### Info notes (deferred, unchanged from initial review)
- Pagination block duplicated between `search-content` and `show-content` — future shared-component candidate
- `with('feed.folder')` on a single bound feed is slightly redundant but harmless