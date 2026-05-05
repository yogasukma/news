# Sprint 006: Read State & Image Styling

## Sprint Goal
Track read articles via localStorage with visual distinction, auto-cleanup after 7 days, and round images in the article modal.

## Duration
2026-05-05 → TBD

## Selected Stories
| Story | Title | Points | Priority |
|-------|-------|--------|----------|
| US-033 | Mark articles as read with localStorage | 3 | P1 |
| US-034 | Round images in article modal | 2 | P2 |

## Sprint Capacity
- Total story points: 5
- Number of stories: 2

---

## Task Breakdown

### US-033: Mark articles as read with localStorage
- [ ] Task 1: Create `resources/js/read-state.js` — manage localStorage read state (add, check, cleanup)
- [ ] Task 2: Store article ID + timestamp when `openArticle()` is called
- [ ] Task 3: On page load and after SPA navigation, apply `.is-read` class to article cards whose IDs are in localStorage
- [ ] Task 4: Add Tailwind styles for `.is-read` state (dimmed title, muted text, reduced opacity)
- [ ] Task 5: Auto-cleanup: remove entries older than 7 days on page load
- [ ] Task 6: Add `read-state.js` to `vite.config.js` entry points and layout `@vite`
- [ ] Task 7: Write tests for the visual classes being present in article card HTML

### US-034: Round images in article modal
- [ ] Task 1: Add Tailwind prose override to round all images inside `#modal-body`
- [ ] Task 2: Write test verifying the CSS class is present in the modal body
