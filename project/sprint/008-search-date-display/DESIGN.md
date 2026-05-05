# Technical Design: Sprint 008 — search-date-display

## 1. Architecture Overview
Single template change — pass `mode="recent"` to the existing article-card component in search results. The component already handles date+time formatting from Sprint 007.

## 2. Technology Stack
No new dependencies. One Blade template modified.

## 3. Detailed Design
- **File**: `resources/views/articles/partials/search-content.blade.php` (line 42)
- **Change**: `<x-partials.article-card :article="$article" />` → `<x-partials.article-card :article="$article" :mode="'recent'" />`
- **Why `mode="recent"`**: Reuses the existing `M j, g:i A` format (e.g., "May 4, 3:45 PM") from the article-card component. No new logic needed.

## 4. Risks & Mitigations
None — this reuses existing, tested component behavior.
