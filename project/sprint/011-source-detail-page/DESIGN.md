# Technical Design: Sprint 011 — source-detail-page

## 1. Architecture Overview

One new public page (`/sources/{feed}`) plus a behavior change on the existing sources list. Pure addition to the existing Laravel 12 Blade + Tailwind v4 + Vite + SQLite stack. **No schema changes, no new dependencies.**

```
GET /sources/{feed} (NEW, name: sources.show)
  → SourcesController::show(Feed $feed)          ← implicit route-model binding → auto 404
  → $feed->articles()->with('feed.folder')->orderByDesc('published_at')->paginate(30)
  → ?fragment=1 ? partial view : full page       (same contract as index()/ArticleController)

Behavior change on GET /sources (existing):
  row <a target="_blank" href="{site_url}">  →  <a data-spa href="/sources/{id}">
  (internal navigation; already-handled by spa.js — no JS changes needed)
```

The SPA layer needs **zero changes**: `spa.js` already (a) intercepts `a[data-spa]` same-origin links, (b) appends `?fragment=1`, (c) swaps `main` content, and (d) re-applies read state. The external site link in the detail header intentionally has NO `data-spa` so it behaves as a normal new-tab link.

## 2. Technology Stack

No new dependencies. Existing stack reused as-is: Laravel 12, PHP 8.2+, SQLite, Blade, Tailwind v4, Vite, Pest 3. Reuses `<x-partials.article-card>` and the pagination block from `articles/partials/search-content.blade.php`.

## 3. Data Model

No schema changes. Reads existing `feeds` columns only:
- `feeds.id` — route key (implicit binding, 404 when missing)
- `feeds.title`, `feeds.favicon_url` (via existing `getFaviconUrlAttribute()` accessor w/ Google fallback)
- `feeds.site_url` — external link in header; must start with `http` to be clickable (same guard as the old sources page)
- `articles.feed_id` — scoping query; `articles.published_at` — ordering

## 4. File Structure

```
routes/web.php                                              ← MODIFY + GET /sources/{feed} (name: sources.show)
app/Http/Controllers/SourcesController.php                  ← MODIFY + show(Feed $feed, Request $request)
resources/views/sources/partials/index-content.blade.php    ← MODIFY rows → internal data-spa links
resources/views/sources/show.blade.php                      ← NEW (layout wrapper, mirrors index.blade.php)
resources/views/sources/partials/show-content.blade.php     ← NEW (header + article list + pagination)
tests/Feature/SourcesPageTest.php                           ← MODIFY 2 tests (US-044)
tests/Feature/SourceDetailPageTest.php                      ← NEW (US-045, US-046)
```

## 5. API Design

Not applicable — public Blade UI. The only JSON endpoint (`/article/{id}` modal data) is untouched and reused by the modal on the new page.

## 6. Design Decisions

### US-044 — Sources list rows navigate to detail page

1. **Row anchor swap** in `sources/partials/index-content.blade.php`: the conditional `$siteUrl` wrapper (`<a target="_blank">` vs plain `<div>`) is replaced by an unconditional single anchor:
   ```blade
   <a href="{{ route('sources.show', $feed) }}" data-spa class="flex items-center gap-2 min-w-0 group">
   ```
   Every row becomes clickable (including the 2 feeds with null `site_url` — the old plain-text branch disappears). Favicon + title (+ folder sep/name) stay inside the link; last-fetched time stays in the right-aligned `shrink-0` div outside the link (preserves the TOC layout and US-040's "time outside link" test contract). `data-spa` makes it SPA-capable with no spa.js change. Row `title` attribute (old `title="{{ $siteUrl }}"`) is dropped — the URL lives on the detail page now.
2. **Route placement**: `Route::get('/sources/{feed}', ...)` registered AFTER `Route::get('/sources', ...)` — exact-match routes resolve before the parameterized one, so `/sources` still hits `index()`.
3. **`show()` controller** (mirrors `index()` fragment contract verbatim):
   ```php
   public function show(Feed $feed, Request $request): Response
   {
       $articles = $feed->articles()
           ->with('feed.folder')
           ->orderByDesc('published_at')
           ->paginate(30);

       $data = ['feed' => $feed, 'articles' => $articles];

       if ($request->query('fragment') === '1') {
           return response()->view('sources.partials.show-content', $data);
       }

       return response()->view('sources.show', $data);
   }
   ```
   Implicit binding (`{feed}` + `Feed $feed`) auto-returns 404 for unknown ids (AC: 404). `paginate(30)` generates `/sources/{id}?page=N` links; spa.js re-appends `fragment=1` on SPA clicks (identical to search pagination).

### US-045 — Detail page header & not-found

4. **View split** follows the established convention: `sources/show.blade.php` = `<x-layouts.app>` + `@include('sources.partials.show-content')` (identical shape to `sources/index.blade.php`).
5. **Header layout** in `show-content.blade.php` — "follows home page design" (single column, same typography/stone palette):
   - "Back to sources" link at top (`href="{{ route('sources') }}"`, `data-spa`, left-chevron + text — same styling as the existing "Back to feeds" link)
   - Header block: favicon `<img>` (`{{ $feed->favicon_url }}`, `w-6 h-6 rounded`, `loading="lazy"`, `onerror` hide — reuses card conventions) + `<h1 class="text-2xl font-bold text-stone-900">{{ $feed->title }}</h1>`
   - Below the title, when `$feed->site_url` starts with `http`: clickable URL `{{ $feed->site_url }}` (`text-sm text-stone-500 hover:text-stone-900`, `target="_blank" rel="noopener noreferrer"`, `title={{ $feed->site_url }}`). When not valid: no link element at all (AC).
   - Article count line: `{{ $articles->total() }} {{ Str('article')->plural($articles->total()) }}` (matches home/search header pattern).

### US-046 — Paginated article list

6. **Article cards**: `<x-partials.article-card :article="$article" :mode="'recent'" />` — recent mode renders `M j, g:i A` (date+time) exactly as search results do (US-037 already set that precedent for multi-date lists). Card click behavior (modal + read-state) is baked into the shared component — no extra wiring.
7. **Pagination block**: copy the search-content pattern verbatim — "Previous / Page X of Y / Next" with `data-spa` on the links. `hasPages()` guard hides controls when a source has ≤ 30 articles.
8. **Empty state**: centered muted "No articles yet." when `$articles->isEmpty()` (mirrors the home page empty state).

### Testing strategy

9. **US-044 (modify existing)**: `SourcesPageTest` — replace the "links each source row to its site homepage in a new tab" test with one asserting `route('sources.show', $feed)` + `data-spa` + **no** `target="_blank"`/`rel="noopener noreferrer"` on the row; replace the "plain, non-clickable text" test with one asserting a null-`site_url` feed still renders a clickable detail link.
10. **US-045 (new file)**: `SourceDetailPageTest` — 200 on valid id (assert title + favicon in header); 404 on `/sources/999999`; valid `site_url` → external anchor with `target="_blank" rel="noopener noreferrer"`; non-http/empty `site_url` → no external anchor; "Back to sources" link present with `data-spa`.
11. **US-046 (new file)**: newest-first ordering (two articles, assert document order); pagination with 35 factory articles (assert "Page 1 of 2" + page-2 link, then GET `?page=2` shows 5 articles + "Page 2 of 2"); date+time format asserted via the same `Carbon` instance → `->format('M j, g:i A')` (never hard-coded); card wires `openArticle({id})` (component contract); empty state text; `?fragment=1` returns content without `<!DOCTYPE html>`.

## 7. Security Considerations

- **Route-model binding** resolves only integer ids and 404s otherwise — no injection surface via the path param.
- **External URL escaped** by Blade `{{ $feed->site_url }}` in `href`; `rel="noopener noreferrer"` on the new-tab link (tab-nabbing prevention). Link only rendered when the stored value passes the `str_starts_with($feed->site_url, 'http')` guard (prevents `javascript:` style values).
- **No user input reaches SQL** — queries use only bound `feed_id` and constants.
- Favicon `<img>` keeps `onerror` hide; article content sanitization unchanged (existing `FeedParser`/modal pipeline untouched).
- Public read-only access model preserved — no auth changes.

## 8. Risks & Mitigations

| Risk | Mitigation |
|------|-----------|
| `/sources/{feed}` shadowing `/sources` | Literal route registered first; exact match wins in Laravel. Verified via `php artisan route:list`. |
| Row link change breaks US-040 tests (time outside link, href/title ordering) | Rewrite the 2 affected tests to the new contract (internal link, time still outside, no external href) rather than patching assertions. |
| Pagination links broken under SPA (fragment param) | Same mechanism as search pagination (spa.js re-appends `fragment=1`); covered by a fragment test on page 2. |
| Feed with null `site_url` — header shows just favicon+title | Accepted per PRD; external link omitted, no layout shift (empty block not rendered). |
| `route('sources.show', $feed)` fails if model binding name mismatches param | Route param `{feed}` + controller type-hint `Feed $feed` + model `Feed` — standard Laravel implicit binding; `route()` helper uses the same key. |