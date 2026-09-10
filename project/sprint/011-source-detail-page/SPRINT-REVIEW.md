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

### US-047: Link icon next to the site URL on the source detail page — DONE
- All acceptance criteria met (2/2)
- Tests passing: 1/1
- Notes: an external-link SVG icon now sits inside the site-URL anchor in the source detail header, making the "opens externally" affordance obvious.

### US-048: Source names link to the source detail page across the app — DONE
- All acceptance criteria met (4/4)
- Tests passing: 2/2
- Notes: every source name shown with an article now links to `/sources/{id}`:
  - Article cards (homepage, date pages, search results, source detail list) — feed name + favicon become a `data-spa` link; card click still opens the modal but the feed link is guarded so it never triggers it
  - Article modal header — feed name (with favicon) is a link that closes the modal and SPA-navigates to the source page

## Stories Not Completed
None.

## Demo Summary
- **Sources page** (`/sources`): clicking any source row now SPA-navigates to its detail page instead of opening the external site in a new tab.
- **Source detail page** (`/sources/{id}`): a "Back to sources" link, then a header with the source's favicon, site name, and a clickable link to the actual website (opens externally in a new tab, with a link icon). Below, the source's articles render as standard article cards (newest first, date+time stamps) with pagination when there are more than 30; clicking a card opens the reading modal as on the homepage.
- **App-wide**: source names in article cards and the reading modal link to `/sources/{id}` (SPA-compatible, except the modal link which closes the modal first).
- Try it: `/sources/1` (or any id from the sources list), `/sources/999999` → 404 page.

## Metrics
- Planned story points: 14
- Delivered story points: 14
- Velocity: 14 points/sprint (previous: 9)
- Full suite: 257 tests, 763 assertions, all green