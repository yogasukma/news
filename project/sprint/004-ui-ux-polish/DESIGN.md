# Technical Design: Sprint 004 — UI/UX Polish

## 1. Architecture Overview

Three independent enhancements to the existing Blade + vanilla JS frontend:

1. **Favicon display** — Use the feed's `site_url` domain to construct a Google Favicon API URL client-side; render as `<img>` in article cards and modal
2. **Hover effects** — Enhanced Tailwind utility classes on the existing article card component
3. **SPA navigation** — "HTML over the wire" approach: intercept date/folder/search clicks, `fetch()` the same URL with `?fragment=1`, swap `<main>` innerHTML, manage History API

No new dependencies. No backend schema changes.

## 2. Technology Stack

- **Favicon**: Google's favicon service (`https://www.google.com/s2/favicons?domain={domain}&sz=32`) — no backend storage needed, no external PHP lib
- **Hover**: Tailwind CSS v4 utility classes only
- **SPA**: Vanilla JavaScript with `fetch()`, `history.pushState()`, `popstate` event — no framework
- **Fragment rendering**: Blade partial returned when `?fragment=1` query param is present

## 3. Data Model

No schema changes. The `feeds` table already has:
- `site_url` (varchar) — used to extract the domain for favicon
- `favicon_url` (varchar) — already in schema but currently unused; we'll populate it during `rss:fetch`

**Favicon URL strategy**: 
- During `rss:fetch`, if `favicon_url` is empty, construct `https://www.google.com/s2/favicons?domain={domain}&sz=32` from the feed's `site_url` and store it
- This avoids runtime external requests for every page load
- Fallback: if no `site_url` exists, use a generic RSS SVG icon

## 4. API Design

### Fragment Rendering

Both `index()` and `search()` will support `?fragment=1`:

- When `request()->query('fragment')` is present, return a **Blade partial** containing only the inner content (no `<x-layouts.app>` wrapper)
- The partial includes: date header, controls, article list, pagination — everything that goes inside `<main>`
- Response type: `text/html` (same as normal, just no layout)

**New Blade partials**:
- `resources/views/articles/partials/index-content.blade.php` — extracted from `index.blade.php`
- `resources/views/articles/partials/search-content.blade.php` — extracted from `search.blade.php`

The original `index.blade.php` and `search.blade.php` will render the same partial wrapped in `<x-layouts.app>`.

### Article JSON (existing)

`GET /article/{article}` — already returns JSON for the modal. No changes needed except adding favicon_url to the response payload.

## 5. File Structure

```
resources/
├── js/
│   ├── app.js          ← Existing (modal logic) — modified to show favicon
│   └── spa.js           ← NEW — SPA navigation controller
├── views/
│   ├── articles/
│   │   ├── index.blade.php      ← Simplified: wraps partial in layout
│   │   ├── search.blade.php     ← Simplified: wraps partial in layout
│   │   └── partials/
│   │       ├── index-content.blade.php   ← NEW — extracted from index
│   │       └── search-content.blade.php  ← NEW — extracted from search
│   └── components/
│       ├── layouts/app.blade.php  ← Modified: add top loading bar
│       └── partials/
│           └── article-card.blade.php  ← Modified: favicon + hover
```

## 6. Design Decisions

### Decision 1: Google Favicon API vs. fetching/storing favicons
**Choice**: Store Google Favicon API URL in `favicon_url` column during fetch.  
**Reason**: Zero-latency display (no runtime external requests), already have the column, simple to implement. Google's service handles fallbacks for missing favicons.

### Decision 2: HTML-over-the-wire vs. JSON API for SPA
**Choice**: HTML fragments (same Blade templates, just without layout wrapper).  
**Reason**: Reuses existing Blade views, no need to duplicate rendering logic in JS, consistent with the project's server-rendered approach. This is the same pattern HTMX/Turbo uses.

### Decision 3: Event delegation vs. re-binding after DOM swaps
**Choice**: Event delegation on `<main>` element.  
**Reason**: When we swap `<main>` innerHTML, all child event listeners are destroyed. Delegating from `<main>` (which persists) avoids re-initialization.

### Decision 4: Fragment via query param vs. Accept header
**Choice**: `?fragment=1` query parameter.  
**Reason**: Simpler, works with direct browser testing, no header negotiation needed. Search engines won't use this param.

## 7. SPA Navigation Flow

```
User clicks date link / folder pill / submits search form
  ↓
spa.js intercepts the event (event delegation on <main>)
  ↓
Show top loading bar (CSS animation)
  ↓
fetch(url + '?fragment=1')
  ↓
Replace <main> innerHTML with response HTML
  ↓
history.pushState({ url }, '', url)
  ↓
Hide loading bar
  ↓
On browser back/forward (popstate):
  → fetch(url + '?fragment=1'), swap, no pushState
```

**Edge cases**:
- Modal open → closing modal, not triggering SPA navigation
- Date picker `<input type="date">` — intercept `onchange`, build URL, trigger SPA fetch
- Pagination links — also intercepted for SPA navigation
- Fetch failure → fall back to `window.location.href = url`

## 8. Security Considerations

- Favicon URLs from Google's service — no user-controlled URL construction risk (domain comes from our DB)
- Fragment HTML is server-rendered (same sanitization as normal views)
- No new attack surface — same routes, same data, just a query param flag

## 9. Risks & Mitigations

| Risk | Mitigation |
|------|-----------|
| Google Favicon API unavailable/down | Browser shows broken image → we add `onerror="this.style.display='none'"` to hide gracefully |
| DOM swap loses scroll position | Scroll `<main>` to top after swap (or `window.scrollTo(0,0)`) |
| JS fails to load → SPA broken | All links are real `<a href>` tags — progressive enhancement, works without JS |
| Fragment param indexed by search engines | Add `<meta name="robots" content="noindex">` in fragment responses |
