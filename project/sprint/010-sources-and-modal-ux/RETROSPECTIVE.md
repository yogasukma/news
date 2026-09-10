# Retrospective: Sprint 010 — sources-and-modal-ux

**Date**: 2026-09-10
**Stories**: 4 (US-040, US-041, US-042, US-043) — 9 points delivered
**Result**: 9/9 points, 241 tests green, backlog emptied.

## What Went Well

- **Root-cause before patching (US-041)**: Instead of "making the backdrop click work", the design phase inspected the DOM stacking and discovered the scroll wrapper sits above the backdrop — the old listener was dead code. This turned a surface fix into a single correct listener on `#modal-overlay` and removed a misleading element binding.
- **SPA pattern reused end-to-end (US-040)**: The `?fragment=1` contract from `ArticleController` was copied exactly, so the new page got fragment navigation "for free" with zero `spa.js` changes — verified by test for both full-page and fragment rendering.
- **Owner refinements landed fast**: Post-review feedback (RSS Sources title, back-to-feeds link, clickable source rows → site homepage) was implemented with 4 new tests and full-doc sync (PRD, BACKLOG ACs, review/test/sprint-review reports) in one commit.
- **Debt paid down**: The stale `RecentFeedsFallbackTest` flagged in Sprint 009's review was fixed — the suite went from "218 + 1 known failure" to a fully green 241.
- **Defensive default**: The site-homepage link renders plain text unless `site_url` is a valid `http(s)` URL — cheap protection against dead/malicious link schemes without extra validation layers.

## What Could Be Improved

- **Test seam for JS behavior**: All tests are still markup/source assertions (no JS runner). The overlay-dismiss and new-tab behaviors would be much stronger with a headless DOM test (e.g., adding Vitest + jsdom). Worth considering if the frontend grows further.
- **Hardcoded-relative-time test slip**: One test initially hardcoded `'3 hours ago'`; the review caught it and switched to the computed `diffForHumans()`. Lesson: when asserting presentational formatting, compute the expectation from the same source values instead of duplicating the formatting logic.
- **Mid-flight feedback loop**: The sources refinements arrived after Phase 8 output. Smooth (single commit + doc sync), but asking one focused UI question at the sprint-planning gate (e.g., "link targets on the Sources page?") would have avoided the rework cycle.

## Actions for Next Sprint

1. If new UI work is scoped, add Vitest + jsdom (or Playwright) so JS behavior ACs run in a real DOM instead of source assertions.
2. Keep the "compute expectations from source values" rule for all time/formatting assertions (codify in a team note).
3. At Phase 3 planning, explicitly ask the owner about link behaviors and page titles for any new public page to front-load such refinements.
4. Project is feature-complete against the PRD (14/14 modules, 43/43 stories). Next sprint should start by asking whether the PRD gets net-new requirements or the product enters maintenance mode.

## Stats

- Planned: 4 stories / 9 pts — Delivered: 4 stories / 9 pts (100%)
- New tests: 20 (16 initial + 4 refinement)
- Full suite: 241 passed / 706 assertions / 0 failed
- Commits: 10 (8 dev + 1 review fix + 1 refinement) + merge into `dev`