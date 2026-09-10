# Retrospective: Sprint 011 — source-detail-page

**Date**: 2026-09-10
**Stories**: 5 (US-044, US-045, US-046, US-047, US-048) — 14 points delivered
**Result**: 14/14 points, 257 tests green, backlog emptied (48/48).

## What Went Well

- **Feasibility gate before design**: The owner asked "is site_url as permalink possible?" before committing. The data check surfaced that `site_url` is NOT unique (3 duplicated hosts: scripting.com, furbo.org, kill-the-newsletter.com) and varies in format (paths, trailing slashes, double slashes). This changed the decision to an id-based permalink — avoiding a silent data-loss bug that a naive implementation would have shipped.
- **Zero SPA changes needed again**: Following the established `?fragment=1` contract and `data-spa` convention, the entire new page (plus pagination and app-wide source-name links) worked with spa.js untouched — the second sprint in a row reusing the pattern for free.
- **Shared component leverage (US-048)**: Wrapping the feed name in `article-card.blade.php` gave the link to homepage, date pages, search, and the source detail list in one edit. The modal guard (`event.target.closest('a')` instead of `stopPropagation()`) preserved spa.js interception while preventing the modal from hijacking link clicks.
- **Folded owner feedback before closing**: The review-phase additions (link icon, source-name links everywhere) were sized as two stories, developed, tested (3 new/updated tests + full doc sync) and re-reviewed in one fast cycle — the sprint stayed open until the increment matched the owner's intent.
- **Security preserved**: External URL kept behind the `str_starts_with('http')` guard, escaped by Blade, `rel="noopener noreferrer"` on new-tab links; modal feed link built with DOM API / `createTextNode` (no `innerHTML` with feed data).

## What Could Be Improved

- **Route-generated absolute URLs in tests**: The `route('sources.show', ...)` helper emits `http://localhost/...`, which twice tripped "no external http link" assertions (Vite asset hrefs on full pages; the back-link on fragments). Fixed by targeting the contract (no `target="_blank"`/`rel` in fragments) instead of string-hunting `href="http`. Lesson: when asserting "no external links", anchor on external-link *attributes*, not on URL prefixes.
- **Mid-sprint scope expansion at the review gate**: US-047/048 arrived after the Phase 8 review was presented. It was handled smoothly (the branch was unmerged), but asking "any additions before we merge?" at the planning gate would have scoped it up front.
- **JS test gap persists**: All JS behavior (modal feed link, card guard) verified via source assertions — no headless DOM. Same action item as Sprint 010; now two sprints old.

## Actions for Next Sprint

1. Consider Vitest + jsdom (or Playwright) for real-DOM tests of JS-driven UI (feed links, modal guard, read-state) — flagged two sprints running.
2. Keep the rule: assert "external link absence" via contract attributes (`target="_blank"`, `rel`) rather than URL prefixes.
3. At Phase 3 planning, ask the owner explicitly about link behaviors for any new public page to front-load follow-up refinements.
4. At Phase 8, confirm additions before merging — the branch-based flow worked, keep it.

## Stats

- Planned: 5 stories / 14 pts — Delivered: 5 stories / 14 pts (100%)
- New tests: 33 net new (17 detail-page + 2 rewritten sources + 12 existing sources re-verified + 2 follow-up)
- Full suite: 257 passed / 763 assertions / 0 failed
- Commits: 9 (6 dev + 1 follow-up feature + 1 follow-up docs + 1 review fix) + merge into `dev`