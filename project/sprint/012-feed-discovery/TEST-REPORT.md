# Test Report: Sprint 012

## Summary
- Total tests (sprint scope): 20 (11 FeedDiscoveryTest + 9 FeedAddCommandTest incl. 5 updated/added)
- Passed: 20
- Failed: 0
- Skipped: 0
- Full suite: 269 passed, 3 failed (**pre-existing on `dev`** — `UiUxPolishTest` ×2, `SourceDetailPageTest` ×1; fail identically without this sprint's changes, unrelated)

## Results by User Story

### US-049: Discover the real feed URL from a website's HTML
| Test | Description | Result |
|------|-------------|--------|
| discovers an RSS feed from an absolute link tag | Absolute `application/rss+xml` href is returned as-is | PASS |
| discovers an Atom feed when no RSS link exists | Atom-only page returns the Atom href (resolved) | PASS |
| prefers the RSS feed link over the Atom feed link | Both present → RSS wins | PASS |
| resolves a root-relative feed href against the site origin | `/feed` → `https://example.com/feed` | PASS |
| resolves a protocol-relative feed href using the site scheme | `//cdn.example.com/feed.xml` → `https://...` | PASS |
| resolves a path-relative feed href against the base path | `feed.xml` under `/blog` → `/feed.xml` | PASS |
| matches rel attributes that are space-separated lists | `rel="alternate alternate"` still matches | PASS |
| handles uppercase type attribute values case-insensitively | `TYPE="APPLICATION/ATOM+XML"` matched | PASS |
| ignores non-feed link types and throws when the page has no feed link | stylesheet/html links skipped → exception | PASS |
| throws a clear error when the website cannot be fetched | HTTP 503 → "Failed to fetch website: HTTP 503" | PASS |
| ignores feed links pointing at non-http protocols | `javascript:`/`ftp:` hrefs safely ignored → exception | PASS |

### US-050: Discovery integration in rss:feed:add command flow
| Test | Description | Result |
|------|-------------|--------|
| subscribes to a feed discovered from a website URL | HTML page → discovery → feed created with `url` = discovered URL, `site_url` = website; output shows "Discovered feed 'Discovered Blog' at ..." | PASS |
| reports already subscribed when the discovered feed URL is a duplicate | Resolved URL already in DB → "Already subscribed", no new feed (count stays 1) | PASS |
| reports an error when the discovered feed fails to parse | Discovered URL returns HTML → "Failed to fetch or parse feed", no feed created | PASS |
| reports an error when a website URL has no discoverable feed link | HTML with no link tag → "No RSS/Atom feed link found on this page", no feed created | PASS |
| rejects an invalid URL | `not-a-url` → "Invalid URL" | PASS |
| rejects a non-http URL | `ftp://...` → "Invalid URL" | PASS |
| rejects a duplicate feed URL (direct, early check) | Existing direct URL → "Already subscribed" without needing a successful fetch (regression guard for restored early check) | PASS |
| subscribes to a valid RSS feed (direct, discovery skipped) | Direct feed URL unchanged: no discovery, subscribed by entered URL | PASS |
| reports error when feed cannot be fetched | HTTP 500 → "Failed to fetch" | PASS |

## Acceptance Criteria Coverage
| Story | Criterion | Test | Status |
|-------|-----------|------|--------|
| US-049 | AC1: HTML with RSS link → `url` = discovered feed, `site_url` = website | `subscribes to a feed discovered from a website URL` (+ discovery RSS test) | PASS |
| US-049 | AC2: HTML with only Atom link → uses Atom URL | `discovers an Atom feed when no RSS link exists` | PASS |
| US-049 | AC3: RSS preferred over Atom | `prefers the RSS feed link over the Atom feed link` | PASS |
| US-049 | AC4: relative href resolved to absolute | `resolves a root-relative / protocol-relative / path-relative ...` | PASS |
| US-049 | AC5: no feed link → clear error, nothing created | `ignores non-feed link types...` + `reports an error when a website URL has no discoverable feed link` | PASS |
| US-049 | AC6: direct feed URL → discovery skipped | `subscribes to a valid RSS feed` (no discovery output asserted) | PASS |
| US-050 | AC1: duplicate check on resolved feed URL | `reports already subscribed when the discovered feed URL is a duplicate` | PASS |
| US-050 | AC2: discovered feed fails to fetch/parse → error, nothing created | `reports an error when the discovered feed fails to parse` | PASS |
| US-050 | AC3: invalid URL rejected before discovery | `rejects an invalid URL`, `rejects a non-http URL` | PASS |
| US-050 | AC4: output confirms discovered title + URL | `subscribes to a feed discovered from a website URL` (asserts `Discovered feed 'Discovered Blog' at https://feeds.example.com/rss.xml`) | PASS |

## Failed Tests (if any)
None in sprint scope. Full-suite failures (`UiUxPolishTest` "US-028 fragment parameter on index/search", `SourceDetailPageTest` "US-045") are pre-existing on the `dev` branch — reproduced with this sprint's changes stashed. Not caused by sprint 012; recommended for a future fix sprint.