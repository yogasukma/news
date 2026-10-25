---
subject: Self RSS feed sprint work
created_at: 2026-10-25
updated_at: 2026-10-25
creator: scrum / Phase 3 — Sprint planning
---
# Sprint Plan: 013 self-rss-feed

## Sprint Goal
Give external readers a public `/rss` feed of the latest stored articles.

## Sprint Summary
This sprint adds a public RSS feed for this site itself. Visitors can subscribe to `/rss` and read the newest articles in their own reader.

## Sprint Description
The site already collects articles from many sources for on-site reading. Readers asked for a way to follow the collection from outside the site. This work delivers a standard feed at `/rss` with the newest articles first, split into pages of 100, where each entry links back to its original source site. It also advertises the feed on article pages so browsers and readers discover it on their own.

## Sprint Goals
- Public `/rss` returns a valid feed with no login.
- Newest articles first, 100 per page through `?page`.
- Each entry links to its source site with full content.
- Article pages advertise `/rss` for auto-discovery.

## Selected Stories
| Story ID | Title | Module | Priority | Dependencies |
|----------|-------|--------|----------|--------------|
| US-051 | Public self RSS feed with pagination | Self RSS Feed | P0 | US-009 |
| US-052 | Advertise self RSS feed for auto-discovery | Self RSS Feed | P2 | US-051 |

## Sprint Capacity
- Number of stories: 2
- Total story points: 7

---

## Task Breakdown

### US-051: Public self RSS feed with pagination (P0)
- [x] Task 1: Add public `GET /rss` route that returns an RSS 2.0 XML response with no auth
- [x] Task 2: Load stored articles ordered newest first and paginate 100 per page via `?page`
- [x] Task 3: Render channel info (site name, homepage link, description) and items (title, original URL, publication date, source name, full content)
- [x] Task 4: Write feature tests for status, content type, order, 100-per-page limit, page 2, and item links to source sites

### US-052: Advertise self RSS feed for auto-discovery (P2)
- [x] Task 1: Add feed discovery link for `/rss` to the article page head
- [x] Task 2: Write feature test that the discovery link exists and points to `/rss`
