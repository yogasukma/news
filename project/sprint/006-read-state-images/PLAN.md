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
- [x] Task 1: Create `resources/js/read-state.js` — manage localStorage (markRead, isRead, cleanup, applyReadState)
- [x] Task 2: Store article ID + timestamp when `openArticle()` is called in `app.js`
- [x] Task 3: On page load and after SPA navigation, apply `.is-read` class to read article cards
- [x] Task 4: Add CSS for `.is-read` state (opacity 0.55, muted title color)
- [x] Task 5: Auto-cleanup entries older than 7 days on page load
- [x] Task 6: Add `read-state.js` to `vite.config.js` and `@vite` in layout
- [x] Task 7: Add `data-article-id` attribute to article-card partial

### US-034: Round images in article modal
- [x] Task 1: Add CSS rule `#modal-body img { border-radius: 0.5rem; }` in `app.css`
