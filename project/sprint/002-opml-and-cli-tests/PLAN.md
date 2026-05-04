# Sprint 002: OPML & CLI Tests

## Sprint Goal
Complete the CLI tooling by adding OPML import/export, writing tests for all existing CLI commands, and verifying single-feed fetching — so the user can bulk-load their real OPML file and trust the full CLI surface.

## Duration
2026-05-04 → TBD

## Selected Stories
| Story | Title | Points | Priority | Code exists? |
|-------|-------|--------|----------|-------------|
| US-020 | Import OPML file | 5 | P1 | ❌ New |
| US-021 | Export OPML file | 3 | P2 | ❌ New |
| US-002 | Unsubscribe from a feed | 2 | P1 | ✅ Untested |
| US-003 | List all feeds | 2 | P1 | ✅ Untested |
| US-004 | Show feed details | 2 | P2 | ✅ Untested |
| US-006 | Delete a folder | 2 | P2 | ✅ Untested |
| US-007 | Move feed into folder | 2 | P1 | ✅ Untested |
| US-008 | List all folders | 2 | P2 | ✅ Untested |
| US-012 | Fetch single feed | 3 | P1 | ✅ Untested |

## Sprint Capacity
- Total story points: 23
- Number of stories: 9

---

## Task Breakdown

### US-020: Import OPML file
- [x] Task 1: Create `OpmlParser` service class — parse OPML XML, extract outlines (folders + feeds), handle nested outlines
- [x] Task 2: Create `rss:opml:import {file}` Artisan command — parse OPML, create folders, create feeds, skip duplicates
- [x] Task 3: Handle HTML entities and special characters in feed titles (e.g., `&amp;`, emoji)
- [x] Task 4: Display import summary (added folders, added feeds, skipped duplicates, errors)
- [x] Task 5: Validate file exists and is valid XML before parsing
- [x] Task 6: Write tests for OPML import with sample OPML fixtures

### US-021: Export OPML file
- [x] Task 1: Create `rss:opml:export {file}` Artisan command — serialize folders and feeds to OPML XML
- [x] Task 2: Group feeds by folder, uncategorized feeds at top level
- [x] Task 3: Include feed title, XML URL, and HTML URL in outline elements
- [x] Task 4: Handle empty state (no feeds)
- [x] Task 5: Write tests for OPML export

### US-002: Unsubscribe from a feed
- [x] Task 1: Verify `FeedRemoveCommand` handles existing feed ID — deletes feed + articles
- [x] Task 2: Verify non-existent feed ID shows error
- [x] Task 3: Verify confirmation prompt and success message
- [x] Task 4: Write tests for all acceptance criteria

### US-003: List all feeds
- [x] Task 1: Verify `FeedListCommand` displays table with ID, title, URL, folder, article count, last fetched
- [x] Task 2: Verify empty state message
- [x] Task 3: Write tests for all acceptance criteria

### US-004: Show feed details
- [x] Task 1: Verify `FeedInfoCommand` shows detailed info for existing feed
- [x] Task 2: Verify non-existent feed ID shows error
- [x] Task 3: Write tests for all acceptance criteria

### US-006: Delete a folder
- [x] Task 1: Verify `FolderDeleteCommand` deletes folder and nullifies feeds' folder_id
- [x] Task 2: Verify non-existent folder ID shows error
- [x] Task 3: Write tests for all acceptance criteria

### US-007: Move feed into folder
- [x] Task 1: Verify `FolderMoveCommand` assigns feed to folder
- [x] Task 2: Verify invalid feed ID shows error
- [x] Task 3: Verify invalid folder ID shows error
- [x] Task 4: Write tests for all acceptance criteria

### US-008: List all folders
- [x] Task 1: Verify `FolderListCommand` displays table with ID, name, feed count
- [x] Task 2: Verify empty state message
- [x] Task 3: Write tests for all acceptance criteria

### US-012: Fetch single feed
- [x] Task 1: Verify `rss:fetch {feed}` fetches single feed and shows results
- [x] Task 2: Verify non-existent feed ID shows "feed not found" error
- [x] Task 3: Write tests for all acceptance criteria
