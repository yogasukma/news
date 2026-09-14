# Sprint Review: Sprint 012 — Feed Discovery

## Sprint Goal
Let `rss:feed:add` accept website URLs by auto-discovering the site's real RSS/Atom feed from its HTML `<link>` tags.
**Status**: Achieved

## Stories Delivered

### US-049: Discover the real feed URL from a website's HTML — DONE
- All 6 acceptance criteria met
- Tests passing: 11/11 (FeedDiscoveryTest)
- Notes: New `App\Services\FeedDiscovery` uses DOMDocument + XPath, prefers RSS over Atom, resolves all four href forms (absolute, protocol-relative, root-relative, path-relative), and rejects non-http(s) schemes. Non-2xx responses and missing feed links raise clear `RuntimeException`s.

### US-050: Discovery integration in rss:feed:add command flow — DONE
- All 4 acceptance criteria met
- Tests passing: 9/9 (FeedAddCommandTest incl. updated dup test; AcceptanceCriteriaTest AC4 updated)
- Notes: Discovery engages only when direct feed parsing fails — direct feed URLs behave exactly as before. Dedupe runs against the **resolved** feed URL; the original website URL is stored in `site_url`. Output confirms `Discovered feed '<title>' at <url>`. Review fix restored the early duplicate check so re-adding an existing-but-down feed still reports "Already subscribed".

## Stories Not Completed (if any)
| Story | Reason | Carry to next sprint? |
|-------|--------|-----------------------|
| — | none | — |

## Demo Summary

```bash
# Direct feed URL — unchanged behavior
php artisan rss:feed:add https://example.com/feed.xml

# Website URL — auto-discovery
php artisan rss:feed:add https://example.com
# → Fetching feed...
# → No feed at the URL; scanning page for RSS/Atom links...
# → Discovered feed 'Example Blog' at https://example.com/feed.xml
# → Subscribed to 'Example Blog' (ID: 42). Found 25 article(s) in feed.

# Website URL with no feed advertised → clear failure, nothing created
# → Failed to fetch or parse feed: Failed to parse feed XML: invalid or
#   malformed XML No RSS/Atom feed link found on this page.
```

`rss:feed:add` now accepts **either** a direct feed URL **or** a website URL whose HTML advertises a feed via `<link rel="alternate" type="application/rss+xml|application/atom+xml">`.

## Metrics
- Planned story points: 7
- Delivered story points: 7
- Velocity: 7 points/sprint
- New tests: 15 added (11 + 4), 1 updated (dup test), 1 AC test updated
- Commits: 8 on `sprint/012-feed-discovery`