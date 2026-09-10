# Sprint 010: sources-and-modal-ux

## Sprint Goal
Deliver the public Sources directory page and finalize the article modal UX — dismiss by clicking outside, safe new-tab links, and responsive images.

## Duration
2026-09-10 → 2026-09-10

## Selected Stories
| Story | Title | Points | Priority |
|-------|-------|--------|----------|
| US-040 | Sources page listing all feeds with last fetch time | 5 | P1 |
| US-041 | Close article modal by clicking outside content | 2 | P1 |
| US-042 | Open modal content links in a new tab | 1 | P2 |
| US-043 | Responsive full-width images in modal content | 1 | P2 |

## Sprint Capacity
- Total story points: 9
- Number of stories: 4

## Additional Work
- Fix stale `RecentFeedsFallbackTest` date-navigation assertion (flagged in Sprint 009 review): recent mode keeps the date picker visible per PRD Module 10, but the test asserts it is hidden.

---

## Task Breakdown

### US-040: Sources page listing all feeds with last fetch time
- [ ] Task 1: Add `Route::get('/sources', [SourcesController::class, 'index'])->name('sources')` to `routes/web.php`
- [ ] Task 2: Create `app/Http/Controllers/SourcesController.php` — `index()` queries ALL feeds (not date-scoped), loads `folder`, sorts by `last_fetched_at` DESC with never-fetched feeds at the bottom (`orderByRaw('last_fetched_at IS NULL, last_fetched_at DESC')`), and returns the fragment view when `?fragment=1` (mirror ArticleController pattern)
- [ ] Task 3: Create `resources/views/sources/index.blade.php` and `resources/views/sources/partials/index-content.blade.php` — TOC-style list: favicon + feed name left-aligned, last fetched time right-aligned; never-fetched feeds show "Never fetched"; empty state when no feeds
- [ ] Task 4: Add a "Sources" link (separator + link with `data-spa`) right after the date picker in `resources/views/articles/partials/index-content.blade.php`
- [ ] Task 5: Write `tests/Feature/SourcesPageTest.php` covering all 6 acceptance criteria (link appears after date picker with separator; lists all feeds regardless of date; favicon+name left / time right; sorted by last fetched desc; never-fetched at bottom; `fragment=1` returns same content without layout)

### US-041: Close article modal by clicking outside content
- [ ] Task 1: Add `id="modal-overlay"` to the scrollable wrapper div in `resources/views/components/layouts/app.blade.php` (around the flex container holding `#modal-content`)
- [ ] Task 2: Update `resources/js/app.js` — replace the single `modalBackdrop` click listener with a delegated click handler on `#modal-overlay` that closes the modal when the click target is NOT inside `#modal-content` and NOT the close button; preserve existing Escape and close-button behavior
- [ ] Task 3: Write tests: feature test asserting modal overlay markup exists + JS source test asserting the outside-click wiring (project convention for JS-only stories)

### US-042: Open modal content links in a new tab
- [ ] Task 1: In `resources/js/app.js` `openArticle()` — after injecting `modalBody.innerHTML`, iterate `#modal-body a` and set `target = '_blank'` and `rel = 'noopener noreferrer'`
- [ ] Task 2: Write tests: JS source assertion for new-tab handling + feature test confirming the "Read original" footer link retains `target="_blank" rel="noopener noreferrer"`

### US-043: Responsive full-width images in modal content
- [ ] Task 1: Extend the `#modal-body img` rule in `resources/css/app.css` — add `width: 100%; max-width: 100%; height: auto;` while preserving existing `border-radius: 0.5rem`
- [ ] Task 2: Write CSS source assertion test verifying the responsive rules coexist with the border-radius rule

### Test Maintenance (Sprint 009 carry-over)
- [ ] Task 1: Fix `tests/Feature/RecentFeedsFallbackTest.php` — change "hides date navigation in recent mode" to assert the date picker (`data-spa-date`) IS present in recent mode (aligned with PRD Module 10: "Date navigation available… visible in Recent Feeds mode")
- [ ] Task 2: Run the full test suite (`php artisan test`) and confirm all green
- [ ] Task 3: Run `vendor/bin/pint --dirty --format agent` on all newly created/modified PHP files

---

## Definition of Done
- All tasks for each story completed and checked
- All acceptance criteria for US-040 → US-043 verified by tests
- Full test suite green
- Artisan commands / routes verified (`php artisan route:list`)