---
subject: Self RSS feed design
created_at: 2026-10-25
updated_at: 2026-10-25
creator: scrum / Phase 4 — Design
---
# Technical Design: 013 self-rss-feed

## 1. Goal & Context
Provide a public RSS 2.0 feed at `/rss` with the newest stored articles first, 100 per page via `?page`. Each entry links to its original source site. Article pages advertise the feed for auto-discovery. Scope is US-051 and US-052 only. `GUIDELINES.md` and `RULES.md` do not exist, so no extra hard constraints apply beyond Laravel conventions found in the codebase.

## 2. Architecture Overview
Add one public read-only endpoint alongside the existing article routes. A small controller loads articles with their feeds, paginates them, and returns a Blade-rendered RSS view with an XML content type. The existing app layout gains one discovery link tag. No changes to fetching, storage, auth, or SPA behavior.

## 3. Technology Stack
- Laravel routing and controller convention as used by `ArticleController` and `SourcesController`.
- Eloquent pagination as already used for search results (30 per page) and source detail (30 per page).
- Blade view rendering XML, following existing Blade partial patterns.
- Pest feature tests as used across `tests/Feature`.

## 4. Data Model
No schema change. Reuse existing models:
- `articles`: `title`, `url` (original source permalink), `content`, `published_at`, `feed_id`.
- `feeds`: `title` (source name) via `article->feed`.
- Order by `articles.published_at` descending. Eager-load `feed` to avoid N+1 queries.

## 5. API Design
- `GET /rss` — public, no auth.
  - Query: `?page` (1-based, default 1, handled by the paginator).
  - Response: `200` with `Content-Type: application/rss+xml; charset=UTF-8`.
  - Body: RSS 2.0 with `channel` (title, link to homepage, description, last build date) and up to 100 `item` entries.
  - Item fields: `title`, `link` = original article `url`, `guid` = same original `url`, `pubDate` in RFC-2822, `source` name plus `description` and `content:encoded` with full stored content.
- No fragment mode, no folder or date filters, no POST or DELETE.

## 6. File Structure
- `routes/web.php` — add `GET /rss`.
- `app/Http/Controllers/RssFeedController.php` — new single-action controller for the feed.
- `resources/views/rss/feed.blade.php` — new Blade view that outputs RSS XML.
- `resources/views/components/layouts/app.blade.php` — add one discovery `<link>` tag in `<head>`.
- `tests/Feature/SelfRssFeedTest.php` — new feature tests for US-051 and US-052.

## 7. Implementation Steps
1. Create `RssFeedController` with an invokable method that queries `Article::with('feed')->orderByDesc('published_at')->paginate(100)` and returns the RSS view with an XML content type.
2. Create `resources/views/rss/feed.blade.php` rendering the RSS 2.0 channel and items, escaping titles and wrapping content in `CDATA` sections.
3. Register `Route::get('/rss', RssFeedController::class)` in `routes/web.php` before parameterized date routes.
4. Add the discovery link tag for `/rss` to the `<head>` in `resources/views/components/layouts/app.blade.php`.
5. Write `SelfRssFeedTest` covering status, content type, order, page size, page 2, source-site links, and the discovery tag.

## 8. Design Decisions
- New `RssFeedController` instead of extending `ArticleController` — because the feed is a separate output format with its own response type, keeping article HTML logic untouched. Rejected: adding a method to `ArticleController`, which would mix HTML, JSON, and XML concerns.
- Blade XML view instead of manual string building — because Blade escaping and layout patterns already exist. Rejected: raw string concatenation, harder to read and easier to break XML.
- Item `link` and `guid` use the original article URL — because the requester expects clicks to go to each source site. Rejected: linking to local article or modal URLs.
- Plain paginator with 100 per page — because it matches the requested `?page` behavior and the existing pagination pattern. Rejected: custom cursor or capped single page.

## 9. Security Considerations
- No authentication on `/rss` by design, same as all public reading routes.
- Only `?page` is accepted as input and it is handled by the framework paginator, so no raw SQL or manual offset handling.
- Titles are escaped in XML; article HTML content is wrapped in `CDATA` so embedded markup cannot break the feed structure.
- No user input is reflected; no secrets or internal paths are exposed.

## 10. Risks & Mitigations
- Invalid XML from special characters or malformed content → escape text nodes, wrap HTML in `CDATA`, verify with feature tests and a feed validator check.
- Large pages (100 full articles) slow to render → eager-load feeds, select only needed columns where practical, keep the view free of extra queries.
- Route conflict with `/date/{date}` pattern → register `/rss` as a literal route before parameterized routes.

## 11. Open Questions
- None for this scope. Channel title and description fall back to the app name and a short generic description.

## 12. Task Mapping
- PLAN US-051 Task 1 (public `GET /rss` route with XML response) → Design Steps 1 and 3.
- PLAN US-051 Task 2 (newest-first query, 100 per page via `?page`) → Design Step 1.
- PLAN US-051 Task 3 (channel info and items with source link and full content) → Design Step 2.
- PLAN US-051 Task 4 (feature tests for feed behavior) → Design Step 5 (US-051 cases).
- PLAN US-052 Task 1 (discovery link in page head) → Design Step 4.
- PLAN US-052 Task 2 (discovery link test) → Design Step 5 (US-052 case).
