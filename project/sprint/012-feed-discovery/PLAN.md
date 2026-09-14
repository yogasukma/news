# Sprint 012: Feed Discovery

## Sprint Goal
Let `rss:feed:add` accept website URLs by auto-discovering the site's real RSS/Atom feed from its HTML `<link>` tags.

## Duration
2026-09-14 → 2026-09-14

## Selected Stories
| Story | Title | Points | Priority |
|-------|-------|--------|----------|
| US-049 | Discover the real feed URL from a website's HTML | 5 | P0 |
| US-050 | Discovery integration in rss:feed:add command flow | 2 | P1 |

## Sprint Capacity
- Total story points: 7
- Number of stories: 2

---

## Task Breakdown

### US-049: Discover the real feed URL from a website's HTML (P0)
- [ ] Task 1: Create `App\Services\FeedDiscovery` service with `discover(string $websiteUrl): string` — fetches the page, extracts `<link rel="alternate">` feed tags (`application/rss+xml` preferred over `application/atom+xml`), resolves relative `href`s against the website origin, throws a clear exception when no feed link is found
- [ ] Task 2: Handle non-2xx responses and non-HTML content in discovery with clear, useful exceptions
- [ ] Task 3: Write unit tests for FeedDiscovery: RSS-only page, Atom-only page, both-feeds→RSS preference, relative href resolution, no-feed-link error, non-2xx response error

### US-050: Discovery integration in rss:feed:add command flow (P1)
- [ ] Task 1: Update `FeedAddCommand::handle()` — when the URL returns an HTML page (direct feed parse fails), run discovery, then parse the discovered feed URL; store `url` = discovered feed URL, `site_url` = the original website URL; duplicate check runs against the resolved feed URL
- [ ] Task 2: Update command output messages to confirm which feed was discovered/subscribed (title + URL)
- [ ] Task 3: Write feature tests for the command flow: duplicate on resolved feed URL, discovered feed that fails fetch/parse, invalid URL rejected before discovery, success output shows discovered title/URL, direct feed URL unchanged