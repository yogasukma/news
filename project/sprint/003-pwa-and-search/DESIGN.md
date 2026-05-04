# Technical Design: Sprint 003 — PWA & Search

## 1. Architecture Overview

This sprint adds two independent features to the public web UI:

```
┌─────────────────────────────────────────────────┐
│               Public Web UI                     │
│                                                  │
│  NEW: Search input in header                    │
│       └── GET /search?q=...&date=...&folder=... │
│       └── ArticleController::search()           │
│       └── Reuses article-card component          │
│                                                  │
│  NEW: PWA support                               │
│       └── public/manifest.json                  │
│       └── public/sw.js (service worker)         │
│       └── public/icons/ (192 + 512)             │
│       └── Meta tags in layout                   │
└─────────────────────────────────────────────────┘
```

## 2. PWA Implementation

### manifest.json (`public/manifest.json`)

Static file served directly by the web server. No PHP processing needed.

```json
{
  "name": "RSS Reader",
  "short_name": "RSS",
  "description": "A minimal, self-hosted RSS reader",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#fafaf9",
  "theme_color": "#1c1917",
  "icons": [
    { "src": "/icons/icon-192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "/icons/icon-512.png", "sizes": "512x512", "type": "image/png" }
  ]
}
```

### Icons (`public/icons/`)

Generate simple SVG-to-PNG icons. Since this is a personal project, use a minimal approach:
- Create `public/icons/icon.svg` — simple RSS icon
- Create `public/icons/icon-192.png` and `icon-512.png` via a simple artisan command or placeholder
- Actually, simplest: use inline SVG as a data URI in the manifest, or generate with a quick script
- **Decision**: Create placeholder PNGs using PHP GD (available in most PHP installs). If GD is unavailable, skip icon generation — the manifest still works without icons.

### Service Worker (`public/sw.js`)

Static file in `public/`, not processed by Vite. Served directly.

**Strategy**:
- **Cache-first** for static assets (CSS, JS, manifest, icons)
- **Network-first** for HTML pages and API calls
- **Cache versioning**: `CACHE_NAME = 'rss-reader-v1'` — increment on deploy

```
Install → cache app shell (/, /manifest.json, /icons/*, CSS/JS bundles)
Fetch   → cache-first for static, network-first for HTML/API
Activate → delete old caches
```

### Layout changes (`resources/views/components/layouts/app.blade.php`)

Add to `<head>`:
```html
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#1c1917">
<link rel="apple-touch-icon" href="/icons/icon-192.png">
```

Add before `</body>`:
```html
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js');
    });
}
</script>
```

## 3. Search Implementation

### Search Strategy: SQLite LIKE

SQLite's `whereFullText` is not supported by Laravel. Options:
- **FTS5 virtual table** — complex migration, SQLite extension dependency, overkill for scale
- **LIKE queries** — simple, works for ~1K-5K articles, no migration needed

**Decision**: Use `LIKE` with `strip_tags` on content. Adequate for personal use.

### Search query approach

Content is stored as HTML (from feeds). We need to search the plain text:
- SQLite `LIKE` on `title` (plain text, direct match)
- For `content`: use `LOWER(content) LIKE ?` which will match substrings within HTML tags too
- **Trade-off**: May match inside HTML tags/attributes. Acceptable — better than missing results.
- Alternative: Add a `content_text` column with stripped HTML. But that requires a migration + backfill. **Deferred to future optimization** — LIKE on raw content works well enough.

### ArticleController::search()

```php
public function search(Request $request): Response
{
    $query = $request->query('q', '');
    $date = $request->query('date');
    $folderSlug = $request->query('folder');
    
    $articles = Article::query()
        ->with('feed.folder')
        ->when($query, function ($q) use ($query) {
            $term = '%' . strtolower($query) . '%';
            $q->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(title) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(content) LIKE ?', [$term]);
            });
        })
        ->when($date, fn ($q) => $q->whereDate('published_at', $date))
        ->when($folderSlug, function ($q) use ($folderSlug) {
            $folder = Folder::where('slug', $folderSlug)->first();
            if ($folder) {
                $q->whereHas('feed', fn ($q) => $q->where('folder_id', $folder->id));
            }
        })
        ->orderByDesc('published_at')
        ->paginate(30);
    
    return response()->view('articles.search', [...]);
}
```

**Key decisions**:
- Case-insensitive search via `LOWER()` — works across SQLite, MySQL, PostgreSQL
- Empty query returns all articles (with pagination) — acts as a browse-all view
- `paginate(30)` — search results need pagination (unlike daily-digest which shows all for one day)
- Reuses `article-card` partial for result display

### Route

```php
Route::get('/search', [ArticleController::class, 'search'])->name('search');
```

### Search UI

Add search input to the header area:

```
┌──────────────────────────────────────┐
│  RSS Reader           [🔍 Search...] │
└──────────────────────────────────────┘
```

- Simple text input with `<form action="/search" method="GET">`
- Search results page at `resources/views/articles/search.blade.php`
- Reuses same folder pills and date display from index
- Empty state: "No results found for 'query'"
- Shows result count: "3 results for 'laravel'"

### Search results view (`resources/views/articles/search.blade.php`)

```
<x-layouts.app>
    <h1>Search results for "{{ $query }}"</h1>
    <p>3 results</p>

    {{-- Folder filter pills (same as index) --}}
    {{-- Article cards (same partial) --}}
    {{-- Pagination links --}}
</x-layouts.app>
```

## 4. File Structure — New/Modified Files

```
public/
├── manifest.json              ← NEW
├── sw.js                      ← NEW
└── icons/
    ├── icon.svg               ← NEW
    ├── icon-192.png           ← NEW
    └── icon-512.png           ← NEW

resources/views/
├── articles/
│   └── search.blade.php       ← NEW
├── components/
│   ├── layouts/
│   │   └── app.blade.php      ← MODIFIED (manifest + SW meta)
│   └── partials/
│       └── search-form.blade.php  ← NEW

app/Http/Controllers/
└── ArticleController.php      ← MODIFIED (add search method)

routes/
└── web.php                    ← MODIFIED (add /search route)

tests/Feature/
└── SearchTest.php             ← NEW
└── PwaTest.php                ← NEW
```

## 5. Design Decisions

### D1: LIKE over FTS5
- **Why**: Simpler, no migration, works for personal scale (~1K-5K articles). FTS5 would require a virtual table migration and isn't supported by Laravel's query builder.
- **Trade-off**: No relevance ranking, no stemming. Acceptable — results ordered by date.

### D2: Static manifest + SW (not Vite-managed)
- **Why**: Service workers and manifests are served from root. Vite hashes filenames which breaks SW cache paths. Static files in `public/` are simpler.
- **Trade-off**: Cache version must be manually bumped in `sw.js` on deploy. Acceptable for personal project.

### D3: Network-first for HTML, cache-first for assets
- **Why**: Articles change frequently (fetch runs hourly), so cached HTML would be stale. Static assets (CSS/JS) are stable and benefit from caching.
- **Impact**: Offline shows cached shell but articles need network — correct UX for a feed reader.

### D4: Pagination on search (30 per page)
- **Why**: Search spans ALL dates, could return hundreds of results. Unlike daily-digest (one day = manageable count), search needs pagination.
- **Impact**: Uses Laravel's `paginate()` with query string preservation.

### D5: Case-insensitive via LOWER()
- **Why**: SQLite `LIKE` is case-insensitive for ASCII by default but NOT for Unicode. Using `LOWER()` ensures consistent case-insensitivity.
- **Trade-off**: Full table scan on every search. Fine for personal scale.

### D6: Placeholder PNG icons via PHP GD
- **Why**: Personal project, doesn't need polished icons. GD is bundled with PHP.
- **Fallback**: If GD unavailable, create 1x1 transparent PNGs — manifest still valid.

## 6. Security Considerations

- **Search input**: User query is parameterized via Eloquent (`?` placeholder) — no SQL injection
- **Search output**: Query displayed in Blade with `{{ }}` (auto-escaped) — no XSS
- **Service worker**: Static file, no user input processing
- **Manifest**: Static JSON, no dynamic content

## 7. Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| LIKE on content with HTML matches inside tags | Acceptable — better over-matching than under-matching |
| Service worker caches stale CSS after deploy | Bump `CACHE_NAME` version in `sw.js` |
| No pagination on daily-digest views | Search uses pagination; daily views stay unpaginated (manageable for one day) |
| Icons look bad (placeholder) | Personal project — can be replaced with real icons later |
| Large article count slows LIKE queries | At 5K articles, LIKE is still fast on SQLite. Add FTS5 if it becomes an issue. |
