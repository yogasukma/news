# Retrospective: Sprint 012 — Feed Discovery

## What went well
- **Discovery-first, zero-regression design**: falling back to discovery only when direct parsing fails kept the existing `rss:feed:add` behavior byte-for-byte intact for direct feed URLs (existing tests never needed changes except AC4's flow-order update).
- **Real-world validation**: tested end-to-end against https://www.joelotter.com/ — discovered `/posts/index.xml` via a root-relative `<link rel="alternate">` tag, parsed 49 articles, exit 0. Confidence boost from a live-site check beyond mocks.
- **Security posture held**: DOMDocument hardened (`LIBXML_NONET`, internal errors), non-http(s) schemes explicitly rejected, every resolved URL re-validated.
- **Discovery was 1 warning in review, 0 criticals** — the early-duplicate regression was caught and fixed in review, not in production.

## What could be improved
- **Task-tracking hygiene**: the PLAN.md checkbox updates sat uncommitted until the merge step — close the loop by committing doc state changes with the phase they belong to.
- **Http::fake pattern gotcha**: `example.com*` matches `feeds.example.com/*` (substring semantics) — cost us a debugging cycle. Use scheme-prefixed patterns (`https://example.com*`) for bare-host URLs.
- **Doc branch drift**: sprint 011's retrospective lived on `main`, never merged to `dev` (had to backfill). Worth checking branch sync before branching next time.

## Action items for next sprint
1. Commit PLAN.md/STATUS.md checkpoint updates together with each phase's deliverable commit.
2. In tests targeting bare-host URLs, prefer `https://host*` patterns over `host/*`.
3. Kick off future sprints only after verifying `dev` is up to date with `main` for project docs.
4. Consider adding an IDN/punycode href case and a query-string href case to FeedDiscovery tests (info-level, low priority).