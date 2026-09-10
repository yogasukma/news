# Sprint Review: Sprint 010 — sources-and-modal-ux

## Sprint Goal
Deliver the public Sources directory page and finalize the article modal UX — dismiss by clicking outside, safe new-tab links, and responsive images.
**Status**: Achieved

## Stories Delivered

### US-040: Sources page listing all feeds with last fetch time — DONE
- All acceptance criteria met (6/6)
- Tests passing: 11/11
- Notes:
  - New `GET /sources` route → `SourcesController::index()`, listing EVERY feed (not date-scoped)
  - TOC-style rows: favicon + feed title (+ folder label) left-aligned, relative fetch time right-aligned ("3 hours ago"); never-fetched feeds render "Never" and sink to the bottom
  - Sorted by `last_fetched_at DESC` (NULLs last via `orderByRaw`), stable `title ASC` tie-break
  - "Sources" link with separator placed immediately after the date picker; `data-spa` enabled, so the page works fragment-style in the SPA (`?fragment=1` path shared with the full page)
  - Empty state + source count header ("N sources")

### US-041: Close article modal by clicking outside content — DONE
- All acceptance criteria met (4/4)
- Tests passing: 4/4
- Notes:
  - Root cause found: the scroll wrapper covers the full viewport ABOVE `#modal-backdrop`, so the previous backdrop listener was dead code
  - `#modal-overlay` id added to the scroll wrapper; one delegated listener closes when the click target is outside `#modal-content` and not the close button — backdrop AND scrollable padding area both close; inside-clicks, close button, and Escape preserved

### US-042: Open modal content links in a new tab — DONE
- All acceptance criteria met (3/3)
- Tests passing: 2/2
- Notes:
  - `openArticle()` re-processes injected body anchors on every open: `target="_blank"` + `rel="noopener noreferrer"`
  - "Read original" footer link (outside `#modal-body`) untouched, still opens new tab

### US-043: Responsive full-width images in modal content — DONE
- All acceptance criteria met (3/3)
- Tests passing: 1/1
- Notes:
  - `#modal-body img` now `width:100%; max-width:100%; height:auto` while preserving `border-radius: 0.5rem`

## Stories Not Completed
| Story | Reason | Carry to next sprint? |
|-------|--------|-----------------------|
| — | All 4 selected stories delivered | — |

## Demo Summary
**Sources page**: From any article page, use the "Sources" link after the date picker (or visit `/sources`). Every subscribed feed appears in a table-of-contents list — icon + name on the left, last fetch time ("3 hours ago" / "Never") on the right, freshest first, never-fetched at the bottom. Full SPA fragment navigation; the URL swap works with browser back/forward.

**Modal UX**: Open any article. Click the dark backdrop or the padding around the white card → modal closes. Click inside the card or the close button → stays open. Escape still closes. Every link inside article content opens a new tab (with `noopener noreferrer`), and images size to the card width without cropping or overflowing.

**Verification**: `php artisan test` → **237 passed, 0 failed** (previously 218 passed + 1 pre-existing failure now fixed). `npm run build` succeeds.

## Metrics
- Planned story points: 9
- Delivered story points: 9
- Velocity: 9 points/sprint

## Observations for the Owner
- The stale `RecentFeedsFallbackTest` failure flagged in Sprint 009 is **fixed** — the date picker is asserted visible in Recent Feeds mode, matching PRD Module 10 and the behavior on `main`.
- Backlog is now **empty** of pending stories: US-040 → US-043 all delivered. The PRD's 14 modules are complete. (Future work would be net-new requirements.)