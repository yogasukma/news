# Code Review: Sprint 011

## Summary
- Files reviewed: 7 (1 controller, 1 route, 2 view partials + 1 layout wrapper, 2 test files)
- Issues found: 0 (Critical: 0, Warning: 0, Info: 2)
- Issues fixed: 0 (2 test assertions were corrected during development after over-broad checks failed — documented below)

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
**Pass** — no critical or warning issues. Two info-level notes (pagination duplication, redundant eager load) are project-convention-consistent and deferred.