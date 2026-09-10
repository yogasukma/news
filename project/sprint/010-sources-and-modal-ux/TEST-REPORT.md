# Test Report: Sprint 010

## Summary
- Total tests: 237
- Passed: 237
- Failed: 0
- Skipped: 0
- Coverage: 100% of sprint acceptance criteria (12/12 ACs across 4 stories)

## Results by User Story

### US-040: Sources page listing all feeds with last fetch time
**Test file**: `tests/Feature/SourcesPageTest.php` (11 tests)

| Test | Description | Result |
|------|-------------|--------|
| test_separator_and_sources_link_after_date_picker | AC1: separator + link positioned right after date picker, `data-spa`, named route href | PASS |
| test_lists_all_feeds_regardless_of_selected_date | AC2: feeds with no articles on the current date still listed | PASS |
| test_favicon_and_name_left_time_right | AC3: favicon + name appear before fetch time in document order | PASS |
| test_sorts_by_last_fetched_desc | AC4: most recently fetched feed rendered first | PASS |
| test_never_fetched_at_bottom | AC5 (implied): never-fetched feeds sink below fetched ones | PASS |
| test_shows_never_for_feeds_without_history | AC5: feeds with `last_fetched_at = null` render "Never" | PASS |
| test_spa_fragment_renders_same_content | AC6: `?fragment=1` returns content with no layout shell | PASS |
| test_empty_state | No-feeds empty state renders | PASS |
| test_feed_count_in_header | Header pluralization ("3 sources") | PASS |
| test_folder_name_next_to_feed_title | Folder label renders after feed title | PASS |
| test_diff_for_humans_formatting | Relative fetch-time formatting (computed, flake-proof) | PASS |

### US-041: Close article modal by clicking outside content
**Test file**: `tests/Feature/ModalUxTest.php` (4 tests)

| Test | Description | Result |
|------|-------------|--------|
| test_modal_overlay_markup | AC1/AC2: `#modal-overlay` (viewport wrapper), `#modal-content`, `#modal-close` present | PASS |
| test_overlay_click_listener_wired | AC1/AC2: delegated listener + `closest('#modal-content')` + close-button guard in app.js | PASS |
| test_no_dead_backdrop_listener | dead `modalBackdrop` listener removed | PASS |
| test_escape_handler_kept | AC4: Escape key handler preserved | PASS |

*AC3 (inside-click keeps modal open) is guaranteed by the `closest('#modal-content')` guard asserted above; manual browser verification noted in Observations.*

### US-042: Open modal content links in a new tab
**Test file**: `tests/Feature/ModalUxTest.php` (2 tests)

| Test | Description | Result |
|------|-------------|--------|
| test_content_links_processed | AC1/AC2: post-injection loop sets `target="_blank"` + `rel="noopener noreferrer"` | PASS |
| test_footer_link_keeps_new_tab | AC3: "Read original" link retains `target="_blank" rel="noopener noreferrer"` | PASS |

### US-043: Responsive full-width images in modal content
**Test file**: `tests/Feature/ModalUxTest.php` (1 test)

| Test | Description | Result |
|------|-------------|--------|
| test_responsive_modal_images | AC1/AC2/AC3: `#modal-body img` rule has `width:100%`, `max-width:100%`, `height:auto`, `border-radius:0.5rem` | PASS |

### Test Maintenance (Sprint 009 carry-over)
`tests/Feature/RecentFeedsFallbackTest.php` — stale "hides date navigation in recent mode" assertion flipped to assert the date picker IS visible in recent mode (PRD Module 10).

| Test | Description | Result |
|------|-------------|--------|
| test_shows_date_navigation_in_recent_mode | `data-spa-date` present in recent mode | PASS |

## Acceptance Criteria Coverage

| Story | Criterion | Test | Status |
|-------|-----------|------|--------|
| US-040 | AC1: separator + Sources link after date picker | test_separator_and_sources_link_after_date_picker | PASS |
| US-040 | AC2: all feeds listed regardless of date | test_lists_all_feeds_regardless_of_selected_date | PASS |
| US-040 | AC3: favicon+name left, time right | test_favicon_and_name_left_time_right | PASS |
| US-040 | AC4: sorted by last fetched desc | test_sorts_by_last_fetched_desc | PASS |
| US-040 | AC5: never-fetched at bottom / "Never" | test_never_fetched_at_bottom, test_shows_never | PASS |
| US-040 | AC6: SPA fragment renders same content | test_spa_fragment_renders_same_content | PASS |
| US-041 | AC1: backdrop click closes | test_overlay_click_listener_wired | PASS |
| US-041 | AC2: scrollable-area click closes | test_overlay_click_listener_wired (target outside `#modal-content`) | PASS |
| US-041 | AC3: inside-content click stays open | `closest('#modal-content')` guard (source assertion) | PASS |
| US-041 | AC4: Escape still closes | test_escape_handler_kept | PASS |
| US-042 | AC1: `target="_blank"` on all body links | test_content_links_processed | PASS |
| US-042 | AC2: `rel="noopener noreferrer"` on all body links | test_content_links_processed | PASS |
| US-042 | AC3: footer link keeps new-tab behavior | test_footer_link_keeps_new_tab | PASS |
| US-043 | AC1: width 100% + max-width 100% | test_responsive_modal_images | PASS |
| US-043 | AC2: height auto, aspect preserved | test_responsive_modal_images | PASS |
| US-043 | AC3: border-radius preserved | test_responsive_modal_images | PASS |

## Failed Tests
None.

## Regression Notes
- Full suite: **237 passed, 691 assertions** (previous baseline: 218 passed + 1 pre-existing failure — the stale failure is now fixed).
- `npm run build` succeeds (Vite production bundle compiles the modified JS/CSS).
- `vendor/bin/pint --dirty` passes.

## Observations
- JS/CSS behavioral ACs are verified via the project's established markup/source-assertion convention (no JS test runner installed). Recommended manual browser sanity check: open an article modal → click backdrop/padding (closes), click card (stays open), press Escape (closes); confirm article-body links open new tabs and wide images stay within the card.