# Technical Design: Sprint 010 — sources-and-modal-ux

## 1. Architecture Overview

One new public page + three targeted frontend refinements, all layered onto the existing Laravel 12 Blade + Tailwind v4 + Vite stack. No schema changes, no new dependencies.

```
GET /sources (new)
  → SourcesController::index()
  → Feed::orderByRaw('last_fetched_at IS NULL, last_fetched_at DESC, title ASC')
  → ?fragment=1 ? partial view : full page        (mirrors ArticleController pattern)

Frontend (existing modal in layouts/app.blade.php):
  #article-modal
   ├─ #modal-backdrop (fixed, bg-black/50)   → behind scroll wrapper
   ├─ #modal-overlay   (scroll wrapper)      → NEW id; delegated click-to-dismiss
   │   └─ #modal-content (white card)        → clicks inside stay open
```

**Key realization (drives US-041):** the scrollable wrapper `.fixed.inset-0.overflow-y-auto` covers the entire viewport and sits ON TOP of `#modal-backdrop`. Clicks on the dark backdrop area actually land on the transparent scroll wrapper (or its flex/padding children) — the existing `modalBackdrop` listener virtually never fires. US-041 corrects this by listening on the wrapper itself and closing whenever the click target is outside `#modal-content`.

## 2. Technology Stack

No new dependencies — existing stack only (Laravel 12, PHP 8.2+, SQLite, Blade, Tailwind v4, Vite, Pest 3). JS/CSS-only changes are plain ES modules + a CSS rule; no test runner is installed for JS, so UI stories follow the project's established markup/source-assertion test convention (see `tests/Feature/UiUxPolishTest.php`).

## 3. Data Model

No schema changes. US-040 reads existing `feeds.last_fetched_at` (datetime, nullable — nullable = never fetched) and `feeds.favicon_url` / `feeds.site_url` (via the existing `getFaviconUrlAttribute()` accessor).

## 4. File Structure

```
routes/web.php                                              ← MODIFY + GET /sources (name: sources)
app/Http/Controllers/SourcesController.php                  ← NEW
resources/views/sources/index.blade.php                     ← NEW (layout wrapper)
resources/views/sources/partials/index-content.blade.php    ← NEW (TOC list + fragment target)
resources/views/articles/partials/index-content.blade.php   ← MODIFY + "Sources" link after date picker
resources/views/components/layouts/app.blade.php            ← MODIFY + id="modal-overlay" on scroll wrapper
resources/js/app.js                                         ← MODIFY overlay-click close + new-tab links
resources/css/app.css                                       ← MODIFY responsive #modal-body img rule
tests/Feature/SourcesPageTest.php                           ← NEW (US-040)
tests/Feature/ModalUxTest.php                               ← NEW (US-041, US-042, US-043)
tests/Feature/RecentFeedsFallbackTest.php                   ← MODIFY stale date-picker assertion
```

## 5. API Design

Not applicable (public Blade UI, no HTTP JSON API beyond the existing `/article/{id}` modal endpoint which is untouched).

## 6. Design Decisions

### US-040 — Sources page

1. **`SourcesController` with a single `index()` action**, mirroring `ArticleController`'s fragment contract: `if ($request->query('fragment') === '1') { return response()->view('sources.partials.index-content', $data); }`. This makes the page SPA-compatible with zero spa.js changes — the link carries `data-spa`, spa.js appends `?fragment=1`, content swaps in `main`.
2. **View split follows the existing convention**: `sources/index.blade.php` = `<x-layouts.app>` + `@include('sources.partials.index-content')` (identical shape to `articles/index.blade.php` and `search.blade.php`).
3. **Query + ordering**: `Feed::query()->with('folder')->orderByRaw('last_fetched_at IS NULL, last_fetched_at DESC, title ASC')->get()`. `last_fetched_at IS NULL` yields 0/1 in SQLite, so NULLs sort last; `title ASC` gives a stable tie-break; explicitly NOT date-scoped (AC #2).
4. **Row layout (TOC style)**: `flex items-center justify-between` — left: favicon `<img>` (w-4 h-4 rounded-sm, `onerror` hide, reusing card conventions) + feed title (escaped); right: `$feed->last_fetched_at?->diffForHumans() ?? 'Never'` (relative "how fresh" phrasing, matching the directory's scanning purpose). Never-fetched feeds render a muted "Never".
5. **Link placement**: the article page's date-navigation row gets a `<span class="text-stone-300">|</span>` separator immediately after the date-picker `<label>`, followed by a "Sources" link styled like the existing nav links (`text-sm`, hover) with `data-spa` and `href="{{ route('sources') }}"` (AC #1).
6. **Empty state**: centered muted "No sources yet." when no feeds exist.

### US-041 — Backdrop/outside click close

7. **Add `id="modal-overlay"` to the existing scroll wrapper** (`div.fixed.inset-0.overflow-y-auto`) — no new elements, no Tailwind changes.
8. **Single delegated listener replaces `modalBackdrop` listener** in `app.js`:
   ```js
   document.getElementById('modal-overlay').addEventListener('click', (e) => {
       if (e.target.closest('#modal-content') || e.target.closest('#modal-close')) return;
       closeModal();
   });
   ```
   Clicks anywhere outside the white card (backdrop, scrollable padding area) close; inside-card and close-button clicks are ignored; Escape key stays untouched (AC #1–#4).
9. **`modalBackdrop` listener is removed** — it was effectively dead code (see Architecture Overview), and keeping it would double-close harmlessly but adds confusion.

### US-042 — New-tab links

10. **Post-injection processing in `openArticle()`**: after `modalBody.innerHTML = article.content`, iterate `modalBody.querySelectorAll('a')` and set `a.target = '_blank'` and `a.rel = 'noopener noreferrer'` (AC #1–#2). The footer "Read original" link lives outside `#modal-body` and already has `target="_blank" rel="noopener noreferrer"` in Blade — unchanged (AC #3). Processing is done per-open, so repeated opens are always safe.

### US-043 — Responsive images

11. **Extend the existing `#modal-body img` rule** in `app.css` by adding `width: 100%; max-width: 100%; height: auto;` while keeping `border-radius: 0.5rem` (AC #1–#3). Pure CSS, preserves aspect ratio, never overflows the `max-w-2xl` card.

### Test maintenance (Sprint 009 carry-over)

12. **Update `RecentFeedsFallbackTest` line ~65**: rename "hides date navigation in recent mode" → "shows date navigation in recent mode" and flip `assertDontSee('data-spa-date')` to `assertSee('data-spa-date', false)`, aligning with PRD Module 10 (date picker + prev/next visible in Recent Feeds mode).

### Testing approach for JS/CSS stories

13. **No JS test runner is installed**, so US-041/042/043 follow the project's existing convention (`UiUxPolishTest`, `ReadStateTest`): HTTP feature tests assert the observable markup contracts (`id="modal-overlay"` present on served page; "Sources" link + separator present; modal footer link retains `target`/`rel`), and source-assertion tests read `resources/js/app.js` / `resources/css/app.css` to pin the behavioral wiring (overlay click handler + `closest('#modal-content')` guard, new-tab processing loop, responsive img rules coexisting with border-radius). Manual browser verification is documented in the test report.

## 7. Security Considerations

- **US-040**: read-only public page; all titles escaped by Blade `{{ }}`; favicon `<img>` keeps `onerror="this.style.display='none'"`; no user input reaches SQL (query uses an `orderByRaw` with only constant expressions — reviewed, no injection surface).
- **US-041**: event delegation only — no new sinks.
- **US-042**: modifying `target`/`rel` on existing anchors of already-injected content; no new injection surface (content via `innerHTML` is pre-existing behavior sourced from feeds).
- **No auth changes** — public read-only access model preserved.

## 8. Risks & Mitigations

| Risk | Mitigation |
|------|-----------|
| `diffForHumans()` output makes US-040 tests flaky | Tests compute the expected string from the same `Carbon` instance they set (`now()->subHours(3)` → assert `$feed->last_fetched_at->diffForHumans()` appears), never hard-code relative text. |
| SPA swap breaks the Sources page (partial includes layout?) | Partial view is layout-free by construction (same pattern as index-content); test asserts no `<!DOCTYPE html>` in `?fragment=1` response. |
| Click inside the card but on `#modal-close` closes AND the guard ignores it | Guard explicitly exempts `#modal-close`; its own dedicated listener still closes. |
| The overflow wrapper's padding area is a child of `#modal-overlay`, so `closest('#modal-content')` logic must treat it as "outside" | Correct by construction: padding div is NOT inside `#modal-content`, so it qualifies as outside → closes (AC #2). |
| Removing the `modalBackdrop` listener regresses some browser where clicks DO land on the backdrop | The overlay wrapper is `fixed inset-0` above the backdrop in ALL modern browsers; the backdrop remains purely visual. Manual browser check included in test report. |