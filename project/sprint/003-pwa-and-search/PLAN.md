# Sprint 003: PWA & Search

## Sprint Goal
Make the RSS reader installable as a PWA on mobile devices and add full-text search across all articles with date/folder filtering.

## Duration
2026-05-04 → TBD

## Selected Stories
| Story | Title | Points | Priority |
|-------|-------|--------|----------|
| US-022 | Web app manifest and icons | 2 | P1 |
| US-023 | Service worker for offline caching | 3 | P2 |
| US-024 | Full-text search | 5 | P3 |
| US-025 | Search with date and folder filters | 3 | P3 |

## Sprint Capacity
- Total story points: 13
- Number of stories: 4

---

## Task Breakdown

### US-022: Web app manifest and icons
- [ ] Task 1: Create `public/manifest.json` with name, short_name, icons, theme_color, background_color, display: standalone, start_url
- [ ] Task 2: Generate placeholder SVG icons (192x192, 512x512) in `public/icons/`
- [ ] Task 3: Add `<link rel="manifest">` and meta tags to layout
- [ ] Task 4: Write tests for manifest route and icon availability

### US-023: Service worker for offline caching
- [ ] Task 1: Create `public/sw.js` service worker with cache-first strategy for app shell
- [ ] Task 2: Register service worker in layout `<script>` tag
- [ ] Task 3: Define CACHE_NAME with version for cache busting on deploy
- [ ] Task 4: Cache app shell assets (HTML, CSS, JS, manifest, icons)
- [ ] Task 5: Handle fetch events — cache-first for static, network-first for API
- [ ] Task 6: Handle activate event — clean old caches
- [ ] Task 7: Write tests verifying SW registration and manifest

### US-024: Full-text search
- [ ] Task 1: Add `search` method to `ArticleController` — query with `LIKE` on title and content
- [ ] Task 2: Add `GET /search` route
- [ ] Task 3: Create search input in the header area of the layout
- [ ] Task 4: Add search results view — reuse article cards, show "no results" empty state
- [ ] Task 5: Strip HTML from content before LIKE comparison (use `strip_tags` or raw SQL)
- [ ] Task 6: Write tests for search endpoint — match in title, match in content, no match, empty query

### US-025: Search with date and folder filters
- [ ] Task 1: Extend search to accept `date` and `folder` query parameters
- [ ] Task 2: Apply date filter (same logic as `index`) to search results
- [ ] Task 3: Apply folder filter to search results
- [ ] Task 4: Search results view shows active folder pills and date context
- [ ] Task 5: Write tests for combined search + date + folder filtering
