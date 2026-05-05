# Technical Design: Sprint 006 — Read State & Image Styling

## 1. Architecture Overview

Two purely frontend changes — no backend modifications needed:

1. **Read state tracking** — New `read-state.js` module manages localStorage. Stores `{articleId: timestamp}` pairs. On page load and after SPA navigation, applies CSS class to read article cards. Cleans up entries older than 7 days.
2. **Rounded images** — CSS-only change via Tailwind `@theme` or prose override in `app.css`.

## 2. Technology Stack

- **localStorage** — browser-native, no library needed
- **Tailwind CSS v4** — prose override for modal images
- **Vanilla JS** — `read-state.js` loaded as separate Vite entry
- No new PHP, no new migrations, no new dependencies

## 3. Data Model

No database changes. localStorage structure:

```json
{
  "rss-read-articles": {
    "1": 1714915200000,
    "2": 1714915800000,
    "5": 1714916400000
  }
}
```

Key = article ID, value = timestamp (ms since epoch). Entries older than 7 days are removed on each page load.

## 4. File Structure

```
resources/
├── js/
│   ├── app.js          ← Modified: call read-state.markRead() in openArticle()
│   ├── spa.js          ← No changes needed (read-state re-runs after DOM swap via MutationObserver or manual call)
│   └── read-state.js   ← NEW — localStorage manager
├── css/
│   └── app.css         ← Modified: prose image rounding override
└── views/
    └── components/
        └── partials/
            └── article-card.blade.php  ← Modified: add data-article-id attribute
```

## 5. Design Decisions

### Decision 1: Single localStorage key with object vs. individual keys
**Choice**: Single key `rss-read-articles` containing a JSON object of `{id: timestamp}` pairs.
**Reason**: Easier to iterate, clean up, and check. Avoids localStorage key pollution.

### Decision 2: How to apply read state after SPA navigation
**Choice**: `read-state.js` exposes an `applyReadState()` function. Called on page load and after SPA DOM swap.
**Reason**: Simple and explicit. The SPA `spa.js` already swaps `<main>` innerHTML — we call `applyReadState()` right after the swap in `navigateTo()`.

### Decision 3: Visual treatment for read articles
**Choice**: Reduced opacity (`opacity-60`), lighter title color (`text-stone-400`), no font-weight change.
**Reason**: Subtle but noticeable. The card is still readable if you want to re-visit, but clearly distinguished from unread.

### Decision 4: Image rounding scope
**Choice**: Target `#modal-body img` via CSS, apply `border-radius: 0.5rem` (rounded-lg).
**Reason**: Only affects images in the article content modal. Article card cover images already have `overflow-hidden` on their container.

## 6. Security Considerations

- localStorage is same-origin — no cross-site concerns
- Article IDs are integers from our DB — no injection risk in localStorage keys
- No data sent to server — purely client-side state

## 7. Risks & Mitigations

| Risk | Mitigation |
|------|-----------|
| localStorage grows unbounded | 7-day auto-cleanup on every page load |
| localStorage disabled in some browsers | Graceful degradation — try/catch, read state simply won't show |
| SPA swap loses read-state classes | `applyReadState()` called after every DOM swap |
