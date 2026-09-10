# Sprint 011: source-detail-page

## Sprint Goal
Add per-source detail pages reachable from the sources page, replacing the new-tab external links with internal navigation to `/sources/{id}`.

## Duration
2026-09-10 → 2026-09-10

## Selected Stories
| Story | Title | Points | Priority |
|-------|-------|--------|----------|
| US-044 | Sources list rows navigate to source detail page | 2 | P0 |
| US-045 | Source detail page — route, header, and not-found handling | 3 | P0 |
| US-046 | Source detail page — paginated article list with modal | 5 | P0 |
| US-047 | Link icon next to the site URL on the source detail page | 1 | P2 |
| US-048 | Source names link to the source detail page across the app | 3 | P1 |

## Sprint Capacity
- Total story points: 14
- Number of stories: 5

---

## Task Breakdown

### US-044: Sources list rows navigate to source detail page
- [x] Task 1: Add `Route::get('/sources/{feed}', [SourcesController::class, 'show'])->name('sources.show')` to `routes/web.php` — route-model binding on `Feed` id auto-returns 404 for unknown ids; keep it AFTER the `/sources` index route
- [x] Task 2: Add `show(Feed $feed)` method to `app/Http/Controllers/SourcesController.php` — loads `feeds.show-content` fragment when `?fragment=1`, full view otherwise (mirror `index()` pattern)
- [x] Task 3: Update `resources/views/sources/partials/index-content.blade.php` — replace the `target="_blank"` external anchor with an internal link `href="{{ route('sources.show', $feed) }}"` + `data-spa`; make EVERY row clickable (drop the `$siteUrl` conditional that rendered non-http sources as plain text)
- [x] Task 4: Update `tests/Feature/SourcesPageTest.php` — change assertions that expect `target="_blank"`/external site links to expect internal `/sources/{id}` links with `data-spa`; update/remove the "invalid site homepage renders as plain text" test (rows are now always links)

### US-045: Source detail page — route, header, and not-found handling
- [x] Task 1: Create `resources/views/sources/show.blade.php` (layout wrapper via `<x-layouts.app>`, include partial)
- [x] Task 2: Create `resources/views/sources/partials/show-content.blade.php` — "Back to sources" link (`data-spa`, href `/sources`) + header showing favicon (`$feed->favicon_url` with lazy-load + onerror hide), site title, and — when `site_url` starts with `http` — a clickable external link (new tab, `rel="noopener noreferrer"`, `title="{{ $siteUrl }}"`)
- [x] Task 3: Write `tests/Feature/SourceDetailPageTest.php` — valid feed id returns 200 with title/favicon in header; missing id (e.g., `/sources/999999`) returns 404; feed with valid site_url renders an external link with `target="_blank"` + `rel="noopener noreferrer"`; feed with non-http site_url renders header WITHOUT external link

### US-046: Source detail page — paginated article list with modal
- [x] Task 1: Extend `show()` in `SourcesController` — `$articles = $feed->articles()->with('feed.folder')->orderByDesc('published_at')->paginate(30)`; pass `feed` + `articles` to the view
- [x] Task 2: In `show-content.blade.php` — render article list with `<x-partials.article-card :article="$article" :mode="'recent'" />` (recent mode shows date+time); empty state `No articles yet.` when empty
- [x] Task 3: Add pagination controls (copy `articles/partials/search-content.blade.php` pattern — Previous/Page X of Y/Next with `data-spa`)
- [x] Task 4: Write tests: newest-first ordering; pagination renders when >30 articles (create 35 via factory, assert page 2 link + articles on page 2); cards show date+time (`M j, g:i A`); clicking card wires `openArticle(id)`; empty state; `?fragment=1` returns content without layout

### US-047: Link icon next to the site URL on the source detail page
- [ ] Task 1: In `show-content.blade.php` — add the external-link SVG icon (same icon as "Read original") inside the site-URL anchor, before the text, with `inline-flex items-center gap-1.5` layout
- [ ] Task 2: Write a test in `SourceDetailPageTest` asserting the icon (svg) renders inside the site-URL anchor when `site_url` is valid

### US-048: Source names link to the source detail page across the app
- [ ] Task 1: In `resources/views/components/partials/article-card.blade.php` — wrap the feed name in `<a href="{{ route('sources.show', $article->feed) }}" data-spa>` (with hover underline); update the card's inline `onclick`/`onkeydown` handlers to bail when `event.target.closest('a')` so clicks on the feed link never open the modal
- [ ] Task 2: In `resources/js/app.js` `openArticle()` — build the modal meta feed name as an `<a href="/sources/{feed.id}">` (data-spa) holding the favicon + title, with a click listener that closes the modal; keep the author/date text nodes as-is
- [ ] Task 3: Feature test — article card (homepage) feed name renders as a `data-spa` link to `route('sources.show', $feed)`, and the card's modal handler is guarded (assert `event.target.closest('a')` guard in the served card markup)
- [ ] Task 4: JS source-assertion test (project convention) — `app.js` builds the modal feed link with `href="/sources/"` and calls `closeModal`; assert via source content (mirror `ModalUxTest` style)

### Sprint Housekeeping
- [ ] Task 1: Run full test suite (`php artisan test`) — all green
- [ ] Task 2: Run `vendor/bin/pint --dirty --format agent` on all created/modified PHP files
- [ ] Task 3: Verify routes with `php artisan route:list` — `/sources` and `/sources/{feed}` both registered

---

## Definition of Done
- All tasks for each story completed and checked
- All acceptance criteria for US-044 → US-048 verified by tests
- Full test suite green
- Routes verified (`php artisan route:list`)