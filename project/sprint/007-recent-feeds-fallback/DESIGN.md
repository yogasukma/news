# Technical Design: Sprint 007 — recent-feeds-fallback

## 1. Architecture Overview

The change is a **server-side enhancement** to the existing `ArticleController::index()` method and two Blade templates. No new files, no new routes, no new models. The SPA system continues to work as-is because it already fetches `?fragment=1` and swaps `<main>` content.

**Flow**:
```
Request / (today)
  → Controller queries today's articles
  → If count < 20 AND date is today → re-query 20 most recent articles
  → Pass $mode ('today' | 'recent') to view
  → Template renders "Recent Feeds" or "Today's Feeds" heading
  → Article card shows date+time or time-only based on $mode
```

## 2. Technology Stack

No new dependencies. Changes are confined to:
- `ArticleController.php` — query logic
- `articles.partials.index-content` — heading
- `components.partials.article-card` — time format

## 3. Data Model

No schema changes. Uses the existing `articles.published_at` column for ordering and the existing `feeds.folder_id` relationship for folder filtering.

## 4. Detailed Design

### 4.1 Controller Changes (`ArticleController::index()`)

**Current behavior**: Always queries `whereDate('published_at', $date)`.

**New behavior**:
1. Query today's articles as before.
2. If `$date->isToday()` AND `$articles->count() < 20`:
   - Re-query: `Article::with('feed.folder')->orderByDesc('published_at')->limit(20)`
   - Apply folder filter if active via `whereHas('feed', ...)`
   - Set `$mode = 'recent'`
3. Otherwise: `$mode = 'today'` (or `'date'` for past dates — same display behavior).

**Key decisions**:
- The fallback **only triggers for today** (`$date->isToday()`). Past dates keep their current behavior.
- We do **two queries** (today first, then recent if needed) rather than a single smart query. This is simpler and the performance cost is negligible for the expected data volume (hundreds of feeds, tens of thousands of articles).
- The folder filter applies to the **same 20-article limit** in recent mode (scoped to that folder's feeds).

**Pseudocode**:
```php
$mode = 'today';

if ($date->isToday() && $articles->count() < 20) {
    $mode = 'recent';
    $articles = Article::query()
        ->with('feed.folder')
        ->when($folder, fn ($q) => $q->whereHas('feed', fn ($q) => $q->where('folder_id', $folder->id)))
        ->orderByDesc('published_at')
        ->limit(20)
        ->get();
}
```

### 4.2 Template: `index-content.blade.php`

**Heading change** (line 4):
- Current: `$currentDate->isToday() ? "Today's Feeds" : $currentDate->format('F j, Y')`
- New: Use `$mode` variable:
  - `'today'` → `"Today's Feeds"`
  - `'recent'` → `"Recent Feeds"`
  - `'date'` (past date) → `$currentDate->format('F j, Y')`

**Article card call** (line 77):
- Current: `<x-partials.article-card :article="$article" />`
- New: `<x-partials.article-card :article="$article" :mode="$mode" />`

**Date navigation**: Hidden or simplified in recent mode. When in recent mode, the date navigation doesn't make semantic sense since we're spanning multiple dates. The prev/next day links should be hidden, but the date picker can remain so the user can navigate to a specific date.

**Empty state**: The "No articles on this date" message never shows in recent mode (we always fill with up to 20 articles). It only shows in today/date mode.

### 4.3 Component: `article-card.blade.php`

**New prop**: `mode` with default value `'today'`.

**Time display** (line 39-41):
- Current: `$article->published_at->format('g:i A')`
- New:
  - When `$mode === 'recent'`: `$article->published_at->format('M j, g:i A')` → e.g., "May 4, 3:45 PM"
  - Otherwise: `$article->published_at->format('g:i A')` → e.g., "3:45 PM" (unchanged)

## 5. File Structure

No new files. Modified files:
```
app/Http/Controllers/ArticleController.php     ← query logic
resources/views/articles/partials/index-content.blade.php  ← heading + pass $mode
resources/views/components/partials/article-card.blade.php ← date format
```

## 6. Design Decisions

1. **Two queries instead of one**: Simpler logic, negligible performance impact. The first query (today) is already indexed; the fallback query on `published_at DESC LIMIT 20` is efficient.
2. **`$mode` as a view variable**: Clean separation — controller decides mode, template reacts. No need to infer mode in the template.
3. **`M j, g:i A` date format in recent mode**: Concise ("May 4, 3:45 PM") — matches the existing style of using short month names in date navigation.
4. **Hide date nav in recent mode**: Showing prev/next day arrows when displaying a multi-day feed is confusing. The date picker remains as an escape hatch to navigate to a specific date.
5. **Threshold of 20**: Hardcoded for now. This could be extracted to a config value later if needed.

## 7. Security Considerations

No new attack surface. The changes are purely read-only query adjustments on an existing public route. No new user inputs are processed.

## 8. Risks & Mitigations

| Risk | Mitigation |
|------|-----------|
| Two queries on homepage could be slow | Negligible — `published_at` is used for ordering, SQLite handles `LIMIT 20` efficiently |
| Folder filter in recent mode returns < 20 articles | Acceptable — if a folder only has 5 recent articles, showing 5 is correct behavior |
| "Recent Feeds" heading could confuse users who expect today-only | The article count and date+time on cards make it clear articles span multiple days |
