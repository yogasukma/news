# Test Report: Sprint 011

## Summary
- Total tests: 254 (full suite)
- Passed: 254
- Failed: 0
- Skipped: 0
- New tests this sprint: 28 (14 new `SourceDetailPageTest` + 2 rewritten `SourcesPageTest` + 12 existing sources tests re-verified)

## Results by User Story

### US-044: Sources list rows navigate to source detail page
Tests in `tests/Feature/SourcesPageTest.php`:
| Test | Description | Result |
|------|-------------|--------|
| test_links_each_source_row_to_its_detail_page | Row links to `/sources/{id}` via `data-spa`, no external href, time outside link | PASS |
| test_renders_sources_without_site_homepage_as_clickable_links | Null `site_url` feed still links to detail page | PASS |

### US-045: Source detail page — route, header, and not-found handling
Tests in `tests/Feature/SourceDetailPageTest.php` (describe block US-045):
| Test | Description | Result |
|------|-------------|--------|
| test_renders_the_source_detail_page_for_a_valid_feed_id | HTTP 200, title + favicon in header | PASS |
| test_returns_404_for_a_non_existent_feed_id | `/sources/999999` → 404 | PASS |
| test_shows_site_url_as_new_tab_link_when_valid | External link with `target="_blank"` + `rel="noopener noreferrer"`, no `data-spa` | PASS |
| test_omits_external_link_when_no_valid_site_url | Null `site_url` → no external link in fragment | PASS |
| test_provides_back_to_sources_link_with_spa | Back link present with `data-spa` | PASS |
| test_renders_same_content_via_fragment | `?fragment=1` → no DOCTYPE, no layout | PASS |

### US-046: Source detail page — paginated article list with modal
Tests in `tests/Feature/SourceDetailPageTest.php` (describe block US-046):
| Test | Description | Result |
|------|-------------|--------|
| test_lists_articles_newest_first | Document order assertion | PASS |
| test_only_lists_articles_belonging_to_that_source | Feed scoping | PASS |
| test_paginates_article_lists_30_per_page | 35 articles → "Page 1 of 2", page 2 has 5 cards | PASS |
| test_shows_date_and_time_on_article_cards | `M j, g:i A` format asserted | PASS |
| test_wires_article_cards_to_open_in_modal | `onclick="openArticle(id)"` | PASS |
| test_shows_empty_state | "No articles yet." | PASS |
| test_shows_article_count_in_header | "3 articles" | PASS |

## Acceptance Criteria Coverage

| Story | Criterion | Test | Status |
|-------|-----------|------|--------|
| US-044 | AC1: click → navigate to `/sources/{feed-id}`, no new tab | links_each_source_row_to_its_detail_page | PASS |
| US-044 | AC2: SPA-compatible `data-spa` | links_each_source_row_to_its_detail_page | PASS |
| US-044 | AC3: no-`site_url` source is clickable | renders_sources_without_site_homepage_as_clickable_links | PASS |
| US-044 | AC4: no `target="_blank"` on rows | links_each_source_row_to_its_detail_page | PASS |
| US-045 | AC1: valid id → HTTP 200 | renders_the_source_detail_page_for_a_valid_feed_id | PASS |
| US-045 | AC2: invalid id → 404 | returns_404_for_a_non_existent_feed_id | PASS |
| US-045 | AC3: header shows favicon + title | renders_the_source_detail_page_for_a_valid_feed_id | PASS |
| US-045 | AC4: valid site_url → new-tab link w/ rel | shows_site_url_as_new_tab_link_when_valid | PASS |
| US-045 | AC5: invalid site_url → no external link | omits_external_link_when_no_valid_site_url | PASS |
| US-045 | AC6: back to sources link | provides_back_to_sources_link_with_spa | PASS |
| US-046 | AC1: newest first | lists_articles_newest_first | PASS |
| US-046 | AC2: paginated 30/page w/ controls | paginates_article_lists_30_per_page | PASS |
| US-046 | AC3: cards show date+time | shows_date_and_time_on_article_cards | PASS |
| US-046 | AC4: click opens modal | wires_article_cards_to_open_in_modal | PASS |
| US-046 | AC5: empty state | shows_empty_state | PASS |
| US-046 | AC6: fragment renders w/o reload | renders_same_content_via_fragment | PASS |

## Failed Tests
None. Two over-broad assertions were corrected during development (documented in REVIEW.md) and all 254 tests pass.

## Tooling
- Full suite: `php artisan test --compact` → 254 passed, 747 assertions, 2.15s
- Formatting: `vendor/bin/pint --dirty --format agent` → 1 style fix applied
- Routes: `php artisan route:list --path=sources` → `sources` (index) + `sources.show` both registered