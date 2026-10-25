---
subject: Self RSS feed test results
created_at: 2026-10-25
updated_at: 2026-10-25
creator: scrum / Phase 7 — Testing
---
# Test Report: 013 self-rss-feed

## Summary
- Total tests: 10 / Passed: 10 / Failed: 0 / Skipped: 0
- Command: `php artisan test --compact --filter=SelfRssFeedTest`
- Full suite: 281 passed, 4 failed (pre-existing failures, reproduced on the clean tree without sprint changes)

## Results by story
| Test | Description | Result |
|------|-------------|--------|
| US-051 feed status | Returns RSS with no login and XML content type | Pass |
| US-051 order | Newest first with channel info and homepage link | Pass |
| US-051 item fields | Title, original URL, date, source name, full content | Pass |
| US-051 page limit | Page 1 holds 100 items when 105 exist | Pass |
| US-051 page 2 | `?page=2` shows the remaining oldest article | Pass |
| US-051 empty | No articles still returns valid XML with zero items | Pass |
| US-051 out-of-range | `?page=9999` returns valid XML with zero items | Pass |
| US-051 CDATA | Content with `]]>` keeps XML valid | Pass |
| US-052 discovery | Homepage head advertises `/rss` | Pass |
| US-052 follow | Discovery URL opens the feed | Pass |

## Acceptance criteria coverage
| Story | Criterion | Test | Status |
|-------|-----------|------|--------|
| US-051 | `/rss` returns valid RSS 2.0 with no login and XML type | feed status | Pass |
| US-051 | Newest first with site name, homepage link, description | order | Pass |
| US-051 | 100 newest on page 1 when over 100 exist | page limit | Pass |
| US-051 | `?page=2` shows the next 100 | page 2 | Pass |
| US-051 | Item has title, original URL, date, source name, full content | item fields | Pass |
| US-052 | Head contains discovery link for `/rss` | discovery | Pass |
| US-052 | Discovery link opens `/rss` | follow | Pass |

## Failed tests
- None from this sprint.
- Known gaps: 4 full-suite failures exist on the clean tree without sprint changes (`ReadStateTest` rounded-image case, `SourceDetailPageTest` source detail case, and 2 `UiUxPolishTest` fragment cases expecting `RSS Reader` text). They are unrelated to `/rss` and left open.
