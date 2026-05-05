# Technical Design: Sprint 002 — OPML & CLI Tests

## 1. Architecture Overview

This sprint adds two new components and tests for 7 existing commands:

```
┌─────────────────────────────────────────────────┐
│                   CLI (Owner)                    │
│                                                  │
│  NEW: rss:opml:import {file}                    │
│       └── OpmlParser service                    │
│  NEW: rss:opml:export {file}                    │
│                                                  │
│  TESTED (existing code):                        │
│  rss:feed:remove {feed}                         │
│  rss:feed:list                                  │
│  rss:feed:info {feed}                           │
│  rss:folder:delete {folder}                     │
│  rss:folder:move {feed} {folder}                │
│  rss:folder:list                                │
│  rss:fetch {feed?}  (single-feed mode)          │
└──────────────┬──────────────────────────────────┘
               │
               ▼
┌──────────────────┐   ┌──────────────────┐
│  OpmlParser      │   │  Database        │
│  (Service) NEW   │   │  SQLite          │
│  - parse()       │   │  - folders       │
│  Returns:        │   │  - feeds         │
│   folders[]      │   │  - articles      │
│   feeds[]        │   └──────────────────┘
│   with folder_id │
└──────────────────┘
```

## 2. New Components

### OpmlParser Service (`app/Services/OpmlParser.php`)

Parses OPML v1.0/v2.0 XML files into a normalized structure. Does NOT create database records — the command handles that.

**OPML structure** (from user's real file):
```xml
<opml version="1.0">
  <head><title>...</title></head>
  <body>
    <outline text="Dev" title="Dev">          <!-- folder -->
      <outline type="rss" text="MDN Blog"     <!-- feed -->
        title="MDN Blog"
        xmlUrl="https://..."
        htmlUrl="https://..." />
    </outline>
    <outline type="rss" ... />                 <!-- uncategorized feed -->
  </body>
</opml>
```

**Parsing rules:**
- Top-level `<outline>` with children → folder (use `text` or `title` attr)
- `<outline>` with `type="rss"` and `xmlUrl` → feed
- Top-level feed outlines (no parent folder) → `folder_id: null`
- HTML entities (`&amp;`) handled by SimpleXML automatically
- Emoji in titles (✍, 💐) preserved as-is

**Return type:**
```php
array{
  title: string,
  folders: array<int, array{name: string, slug: string}>,
  feeds: array<int, array{
    title: string,
    url: string,
    site_url: ?string,
    folder_name: ?string
  }>
}
```

### OpmlImportCommand (`app/Console/Commands/OpmlImportCommand.php`)

```
php artisan rss:opml:import {file}
```

**Flow:**
1. Validate file exists and is readable
2. Parse via `OpmlParser::parse()`
3. Create folders (skip if slug exists, re-use existing)
4. Create feeds:
   - Match by `url` — skip if already subscribed
   - Match `folder_name` to find folder ID
5. Display summary: `Imported: X folders, Y feeds. Skipped: Z duplicates.`

### OpmlExportCommand (`app/Console/Commands/OpmlExportCommand.php`)

```
php artisan rss:opml:export {file}
```

**Flow:**
1. Load all folders with their feeds
2. Load uncategorized feeds (folder_id = null)
3. Build OPML XML using `DOMDocument` (for proper escaping)
4. Write to file
5. Display: `Exported X feeds in Y folders to {file}`

**Output structure:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<opml version="1.0">
  <head><title>RSS Reader Export</title></head>
  <body>
    <!-- Uncategorized feeds first -->
    <outline type="rss" text="Feed Title" xmlUrl="..." htmlUrl="..." />
    <!-- Then foldered feeds -->
    <outline text="Dev" title="Dev">
      <outline type="rss" text="Feed Title" xmlUrl="..." htmlUrl="..." />
    </outline>
  </body>
</opml>
```

## 3. Test Design for Existing Commands

All tests use the existing factories and `RefreshDatabase` (already in `tests/Pest.php`).

### Test Strategy

| Command | Key test scenarios |
|---------|-------------------|
| `rss:feed:remove` | Existing feed → confirms → deleted with articles; Non-existent → error; Cancel → no deletion |
| `rss:feed:list` | Multiple feeds → table with all columns; Empty → "No feeds found"; Feeds with/without folder |
| `rss:feed:info` | Existing feed → all details shown; Non-existent → error; Feed with folder and without |
| `rss:folder:delete` | Existing folder → confirms → feeds become uncategorized; Non-existent → error; Cancel → no deletion |
| `rss:folder:move` | Valid feed+folder → moved; Invalid feed → error; Invalid folder → error |
| `rss:folder:list` | Multiple folders → table with feed counts; Empty → "No folders found" |
| `rss:fetch {feed}` | Existing feed ID → fetches single feed; Non-existent ID → "No feeds to fetch" (empty collection) |

### Testing patterns

Commands with `confirm()` use `$this->artisan(...)->expectsConfirmation(...)`:
```php
$this->artisan('rss:feed:remove', ['feed' => $feed->id])
    ->expectsConfirmation("Unsubscribe from '{$feed->title}'? ...", 'yes')
    ->expectsOutput("Unsubscribed from '{$feed->title}'")
    ->assertSuccessful();
```

Commands with tables use `expectsOutput` for surrounding text (table output is hard to assert exactly).

## 4. File Structure — New Files

```
app/
├── Services/
│   └── OpmlParser.php                    ← NEW
├── Console/Commands/
│   ├── OpmlImportCommand.php             ← NEW
│   └── OpmlExportCommand.php             ← NEW

tests/
├── Feature/
│   ├── OpmlImportCommandTest.php         ← NEW
│   ├── OpmlExportCommandTest.php         ← NEW
│   ├── FeedRemoveCommandTest.php         ← NEW
│   ├── FeedListCommandTest.php           ← NEW
│   ├── FeedInfoCommandTest.php           ← NEW
│   ├── FolderDeleteCommandTest.php       ← NEW
│   ├── FolderMoveCommandTest.php         ← NEW
│   ├── FolderListCommandTest.php         ← NEW
│   └── FetchSingleFeedTest.php           ← NEW
├── Unit/
│   └── OpmlParserTest.php                ← NEW
```

## 5. OPML Test Fixtures

Create `tests/fixtures/opml/` directory with:

- **valid.opml** — 2 folders with 3 feeds each, plus 2 uncategorized feeds
- **empty.opml** — Valid OPML with no outlines in body
- **no-folders.opml** — Only flat feed outlines (no nesting)
- **invalid.xml** — Malformed XML for error testing
- **special-chars.opml** — Emoji, HTML entities, long titles

## 6. Design Decisions

### D1: OpmlParser returns data, commands handle persistence
- **Why**: Separation of concerns — parser is testable in isolation, command handles DB logic (dedup, folder matching)
- **Trade-off**: Command is slightly more complex, but parser is pure and reusable

### D2: Folder matching by slug (not name)
- **Why**: Slugs are normalized, avoids case-sensitivity issues ("Dev" vs "dev")
- **Impact**: If user imports same OPML twice, folders are re-used by slug match

### D3: Feed dedup by URL during import
- **Why**: OPML doesn't include feed IDs, URL is the unique identifier
- **Impact**: If same feed URL exists in two OPML folders, second occurrence is skipped

### D4: DOMDocument for OPML export (not SimpleXML)
- **Why**: DOMDocument produces properly formatted/indented XML and handles encoding/escaping reliably
- **Impact**: Slightly more verbose code, but correct output guaranteed

### D5: Separate test files per command
- **Why**: Easier to locate failures, cleaner git history, follows Pest convention
- **Alternative rejected**: One massive `CliCommandsTest.php` — harder to maintain

### D6: FetchFeedsCommand single-feed mode test uses Http::fake()
- **Why**: Don't want to hit real URLs in tests; already established pattern from Sprint 001
- **Pattern**: Same as existing `FetchFeedsCommandTest` but testing the `{feed}` argument path

## 7. Security Considerations

- **OPML parsing**: Uses same `LIBXML_NOCDATA | LIBXML_NONET` flags as FeedParser — prevents XXE
- **File path validation**: Import command validates file exists and is within allowed paths (no directory traversal)
- **No web routes**: OPML commands are CLI-only, no HTTP exposure

## 8. Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| Real OPML has ~200 feeds — import creates many HTTP requests if "fetch now" | Import command does NOT auto-fetch; user runs `rss:fetch` separately after import |
| Some OPML feeds have stale/dead URLs | Already handled by FetchFeedsCommand error resilience |
| OPML with deeply nested outlines | Parser only handles 1 level of nesting (folder → feeds) — matches real-world OPML convention |
| SQLite performance with 200+ feeds | `rss:fetch` processes sequentially, acceptable for personal use |
