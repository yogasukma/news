# Sprint Review: Sprint 011 — source-detail-page

## Sprint Goal
Add per-source detail pages reachable from the sources page, replacing the new-tab external links with internal navigation to `/sources/{id}`.
**Status**: Achieved

## Stories Delivered

### US-044: Sources list rows navigate to source detail page — DONE
- All acceptance criteria met (4/4)
- Tests passing: 2/2 (rewritten in `SourcesPageTest`)
- Notes: every source row is now a `data-spa` internal link to `/sources/{id}`; rows for feeds without a valid `site_url` are clickable too (previously plain text). The last-fetched time remains outside the link, preserving the TOC layout.

### US-045: Source detail page — route, header, and not-found handling — DONE
- All acceptance criteria met (6/6)
- Tests passing: 6/6
- Notes: header shows favicon + site title + clickable site URL (new tab, `rel="noopener noreferrer"`) when `site_url` is valid; otherwise the link is omitted. Unknown ids (e.g. `/sources/999999`) return 404 via route-model binding. "Back to sources" link is SPA-compatible.

### US-046: Source detail page — paginated article list with modal — DONE
- All acceptance criteria met (6/6)
- Tests passing: 7/7
- Notes: articles listed newest first, paginated 30/page with SPA-friendly Previous/Next controls; cards show date+time (`M j, g:i A`) and open in the existing reading modal; empty state shown when a source has no articles; `?fragment=1` works.

## Stories Not Completed
None.

## Demo Summary
- **Sources page** (`/sources`): clicking any source row now SPA-navigates to its detail page instead of opening the external site in a new tab.
- **Source detail page** (`/sources/{id}`): a "Back to sources" link, then a header with the source's favicon, site name, and a clickable link to the actual website (opens externally in a new tab). Below, the source's articles render as standard article cards (newest first, date+time stamps) with pagination when there are more than 30; clicking a card opens the reading modal as on the homepage.
- Try it: `/sources/1` (or any id from the sources list), `/sources/999999` → 404 page.

## Metrics
- Planned story points: 10
- Delivered story points: 10
- Velocity: 10 points/sprint (previous: 9)
- Full suite: 254 tests, 747 assertions, all green