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
- [ ] Task 1: Create `OpmlParser` service class — parse OPML XML, extract outlines (folders + feeds), handle nested outlines
- [ ] Task 2: Create `rss:opml:import {file}` Artisan command — parse OPML, create folders, create feeds, skip duplicates
- [ ] Task 3: Handle HTML entities and special characters in feed titles (e.g., `&amp;`, emoji)
- [ ] Task 4: Display import summary (added folders, added feeds, skipped duplicates, errors)
- [ ] Task 5: Validate file exists and is valid XML before parsing
- [ ] Task 6: Write tests for OPML import with sample OPML fixtures

### US-021: Export OPML file
- [ ] Task 1: Create `rss:opml:export {file}` Artisan command — serialize folders and feeds to OPML XML
- [ ] Task 2: Group feeds by folder, uncategorized feeds at top level
- [ ] Task 3: Include feed title, XML URL, and HTML URL in outline elements
- [ ] Task 4: Handle empty state (no feeds)
- [ ] Task 5: Write tests for OPML export

### US-002: Unsubscribe from a feed
- [ ] Task 1: Verify `FeedRemoveCommand` handles existing feed ID — deletes feed + articles
- [ ] Task 2: Verify non-existent feed ID shows error
- [ ] Task 3: Verify confirmation prompt and success message
- [ ] Task 4: Write tests for all acceptance criteria

### US-003: List all feeds
- [ ] Task 1: Verify `FeedListCommand` displays table with ID, title, URL, folder, article count, last fetched
- [ ] Task 2: Verify empty state message
- [ ] Task 3: Write tests for all acceptance criteria

### US-004: Show feed details
- [ ] Task 1: Verify `FeedInfoCommand` shows detailed info for existing feed
- [ ] Task 2: Verify non-existent feed ID shows error
- [ ] Task 3: Write tests for all acceptance criteria

### US-006: Delete a folder
- [ ] Task 1: Verify `FolderDeleteCommand` deletes folder and nullifies feeds' folder_id
- [ ] Task 2: Verify non-existent folder ID shows error
- [ ] Task 3: Write tests for all acceptance criteria

### US-007: Move feed into folder
- [ ] Task 1: Verify `FolderMoveCommand` assigns feed to folder
- [ ] Task 2: Verify invalid feed ID shows error
- [ ] Task 3: Verify invalid folder ID shows error
- [ ] Task 4: Write tests for all acceptance criteria

### US-008: List all folders
- [ ] Task 1: Verify `FolderListCommand` displays table with ID, name, feed count
- [ ] Task 2: Verify empty state message
- [ ] Task 3: Write tests for all acceptance criteria

### US-012: Fetch single feed
- [ ] Task 1: Verify `rss:fetch {feed}` fetches single feed and shows results
- [ ] Task 2: Verify non-existent feed ID shows "feed not found" error
- [ ] Task 3: Write tests for all acceptance criteria
