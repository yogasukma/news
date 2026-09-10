# Technical Design: Sprint 009 — data-integrity

## 1. Architecture Overview

Three independent changes to the existing Laravel 12 application:

1. **US-038 — Global dedup by URL**: Change `FetchFeedsCommand::storeArticle()` so the normalized article URL is the single source of truth, checked against **all** articles (not scoped to `feed_id`). A one-time migration de-duplicates existing rows and adds a unique index on `articles.url` for enforcement + fast lookups.
2. **US-039 — Feed recovery**: A new artisan command `rss:feed:recover` re-enables disabled feeds that haven't been updated (via `updated_at`) in over a month. Registered in the scheduler.
3. **US-040 — Sources page**: A new public route `/sources` listing every feed (favicon + name left, last fetched time right, TOC-style), ordered by most recently fetched. Supports the existing SPA `?fragment=1` mechanism.

```
Fetch rss:fetch
  → storeArticle(): normalize URL → Article::where('url', $normalized)->first()
  → match? update mutable fields. no match? create.

Scheduler (daily) → rss:feed:recover → re-enable feeds: is_enabled=false AND updated_at < now-1month

GET /sources → SourcesController::index() → feeds ordered by last_fetched_at DESC (NULL last)
  → full page | ?fragment=1 → partial (SPA swap)
```

## 2. Technology Stack

No new dependencies. Existing stack only (Laravel 12, PHP 8.2+, SQLite, Blade + Tailwind v4, Pest 3).

## 3. Data Model

### Schema change (US-038 only)

One migration on `articles`:

1. **Dedupe first** (raw SQL, SQLite-safe):
   ```sql
   DELETE FROM articles WHERE id NOT IN (SELECT MIN(id) FROM articles GROUP BY url);
   ```
   Keeps the oldest record per URL (stable `published_at`, original `feed_id`).
2. **Add unique index**:
   ```php
   Schema::table('articles', function (Blueprint $table) {
       $table->unique('url');
   });
   ```
   DB-level enforcement = defense in depth behind the application check.

No schema changes for US-039 / US-040. US-039 reads `feeds.is_enabled` + `feeds.updated_at` (already present). US-040 reads `feeds.last_fetched_at` (already present, datetime cast).

## 4. API Design

### New route

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET | `/sources` | `sources` | `SourcesController@index` |

`index()`:
- `Feed::with('folder')->orderByDesc('last_fetched_at')->get()`
  - SQLite sorts `NULL`s last on `DESC` → never-fetched feeds naturally land at the bottom.
- Data passed: `feeds`.
- `?fragment=1` → `sources.partials.index-content` (SPA fragment), else full page `sources.index`.

## 5. File Structure

```
database/migrations/XXXX_add_unique_url_to_articles_table.php   ← NEW (US-038)
app/Models/Article.php                                          ← + normalizeUrl() static (US-038)
app/Console/Commands/FetchFeedsCommand.php                      ← refactor storeArticle() (US-038)
app/Console/Commands/FeedRecoverCommand.php                     ← NEW (US-039)
routes/console.php                                              ← + daily schedule (US-039)
app/Http/Controllers/SourcesController.php                      ← NEW (US-040)
routes/web.php                                                  ← + /sources route (US-040)
resources/views/sources/index.blade.php                         ← NEW full page (US-040)
resources/views/sources/partials/index-content.blade.php        ← NEW fragment (US-040)
resources/views/articles/partials/index-content.blade.php       ← + Sources link after datepicker (US-040)
tests/Feature/FetchDedupTest.php                                ← NEW (US-038)
tests/Feature/FeedRecoverCommandTest.php                        ← NEW (US-039)
tests/Feature/SourcesPageTest.php                               ← NEW (US-040)
```

## 6. Design Decisions

### US-038
1. **URL is the primary dedup key, checked globally.** External IDs (`guid`/Atom `id`) remain stored but no longer drive uniqueness — guids change/disappear between feeds, URLs are the stable permalink the user asked for.
2. **Normalize before store AND before lookup.** `Article::normalizeUrl()` strips known tracking params (`utm_*`, `fbclid`, `gclid`, `mc_cid`, `mc_eid`, `ref`, `source`) via `parse_url` + `http_build_query`. Storing the cleaned URL keeps the DB consistent with the unique index and prevents `?utm_source=x` vs `?utm_source=y` dupes (US-038 AC #4).
3. **Update-on-match preserves `feed_id` and `published_at`.** If the same URL arrives from a second feed, we refresh title/content/author/cover_image on the existing row — the article appears once (US-038 AC #2), keeping the requirement "no article stored twice".
4. **Unique index + cleanup migration.** Existing duplicates (e.g., pre-fix guid-drift articles) are collapsed to the oldest row before the index is added; otherwise the migration would fail.
5. **`normalizeUrl()` lives on `Article`** (static, public) so the command and unit tests share one implementation — no new base folders needed.

### US-039
6. **`updated_at` signals inactivity.** Disabled feeds are skipped by `rss:fetch` (`where is_enabled = true`), so their `updated_at` freezes at the moment of disable — exactly "time since the feed stopped being maintained". A feed disabled over a month ago is considered stale and recoverable.
7. **Recovery = `is_enabled=true`, `error_count=0`, `last_error=null`.** After recovery the feed participates in the next scheduled fetch; if it still fails it re-accumulates errors and re-disables after 8 — no infinite churn.
8. **Separate scheduled command (user's choice), run daily.** Keeps `rss:fetch` focused; recovery is a rare, deliberate maintenance action with its own CLI visibility (table of recovered feeds + count).

### US-040
9. **TOC-style rows**: favicon + feed name left-aligned, last-fetched time right-aligned, subtle divider — consistent with the app's minimal stone-stone typography.
10. **`orderByDesc('last_fetched_at')`**: most recent fetch first (user requirement); SQLite naturally places `NULL` (never-fetched) rows last.
11. **SPA integration via `?fragment=1`** — identical pattern to `ArticleController::index`/`search`. The new link uses `data-spa` so the SPA intercepts it, fetches the fragment, and swaps `<main>` without reload.
12. **Link placement**: after the date-picker `<input>` inside the existing controls row, separated by the same `|` divider style already used between prev/next day links.

## 7. Security Considerations

- **US-038**: `normalizeUrl()` operates only on already-fetched feed data (`parse_url`/`parse_str` on strings) — no user-supplied input. The cleanup migration deletes only rows whose URL duplicates another; no new attack surface.
- **US-039**: CLI-only command, no web exposure. Output is feed titles (safe, plain-text table).
- **US-040**: Public read-only page. Feed favicons are external `https` images rendered as `<img>` (never executed); feed names escaped by Blade. No query-string user input processed beyond the existing `fragment` flag.

## 8. Risks & Mitigations

| Risk | Mitigation |
|------|-----------|
| Cleanup migration deletes a "better" duplicate (keeps MIN(id) instead of a later, richer row) | Acceptable: URL-driven dedup means the articles are near-identical; keeping the oldest preserves original `published_at`/source. Migration runs once. |
| New URL normalization causes two previously-separate articles to collapse | Desired behavior (they were duplicates). Unique index prevents future divergence. |
| `rss:feed:recover` re-enables a feed that is truly dead → it burns 8 fetch cycles before re-disabling | Acceptable & intentional: a monthly probe is the point of the feature; re-disable threshold already exists. |
| `orderByDesc` NULL placement differs across DBs | Target DB is fixed (SQLite, per `database.default`); behavior verified in tests. |
| SPA fragment for `/sources` must not include layout | Follows existing `articles.index` / `articles.partials.index-content` split exactly. |