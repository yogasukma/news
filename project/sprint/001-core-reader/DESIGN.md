# Technical Design: Sprint 001 — Core Reader

## 1. Architecture Overview

```
┌─────────────────────────────────────────────────┐
│                   CLI (Owner)                    │
│  rss:feed:add/remove/list/info                  │
│  rss:folder:create/delete/move/list             │
│  rss:fetch                                      │
│  routes/console.php (scheduler)                 │
└──────────────┬──────────────────┬───────────────┘
               │                  │
               ▼                  ▼
┌──────────────────┐   ┌──────────────────┐
│  FeedParser      │   │  Database        │
│  (Service)       │   │  SQLite          │
│  - parseRss()    │   │  - folders       │
│  - parseAtom()   │   │  - feeds         │
│  - autoDetect()  │   │  - articles      │
└──────────────────┘   └──────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────┐
│               Public Web UI                     │
│  GET /              → Today's articles           │
│  GET /date/{date}   → Articles for date          │
│  GET /article/{id}  → Article JSON (for modal)   │
│                                                  │
│  ArticleController → Blade views + Tailwind CSS  │
└─────────────────────────────────────────────────┘
```

- **CLI commands** manage data (feeds, folders) and trigger fetching
- **FeedParser service** encapsulates RSS/Atom parsing logic
- **Public web UI** is read-only — no auth, no state, no mutations
- **SQLite** stores everything — suitable for single-user scale

## 2. Technology Stack

| Layer | Choice | Justification |
|-------|--------|---------------|
| Framework | Laravel 12 | Already in project |
| Database | SQLite | Already configured, sufficient for personal use |
| Frontend | Blade + Tailwind CSS v4 | Already configured via Vite |
| RSS Parsing | PHP SimpleXML + DOMDocument | Built-in, no external dependency needed |
| HTTP Client | Laravel Http facade | Built-in, handles timeouts and errors |
| JS for modal | Vanilla JavaScript | Minimal interactivity, no framework overhead |
| Testing | Pest | Already configured |

**No new Composer/NPM dependencies required.**

## 3. Data Model

### 3.1 Folders Table

```sql
CREATE TABLE folders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    created_at DATETIME,
    updated_at DATETIME
);

-- Index: slug (unique) for lookups and URL routing
```

### 3.2 Feeds Table

```sql
CREATE TABLE feeds (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255) NOT NULL,
    url VARCHAR(2048) NOT NULL UNIQUE,
    site_url VARCHAR(2048) NULL,
    description TEXT NULL,
    favicon_url VARCHAR(2048) NULL,
    folder_id INTEGER UNSIGNED NULL,
    last_fetched_at DATETIME NULL,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (folder_id) REFERENCES folders(id) ON DELETE SET NULL
);

-- Index: url (unique) for duplicate detection
-- Index: folder_id for folder-based queries
```

### 3.3 Articles Table

```sql
CREATE TABLE articles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    feed_id INTEGER UNSIGNED NOT NULL,
    title VARCHAR(1000) NOT NULL,
    url VARCHAR(2048) NOT NULL,
    content TEXT NULL,
    author VARCHAR(255) NULL,
    published_at DATETIME NOT NULL,
    cover_image VARCHAR(2048) NULL,
    external_id VARCHAR(1000) NULL,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (feed_id) REFERENCES feeds(id) ON DELETE CASCADE
);

-- Index: feed_id for feed-based queries
-- Index: published_at for date-based queries (primary access pattern)
-- Index: external_id + feed_id (unique) for deduplication
-- Index: feed_id + published_at for folder+date filtered queries
```

### Entity Relationships

```
Folder  1──N  Feed  1──N  Article
```

- A folder has many feeds (feeds belong to a folder, nullable)
- A feed has many articles (articles belong to a feed, cascade delete)
- Feeds without a folder are "uncategorized"

## 4. API Design

### 4.1 Web Routes (Public, Read-Only)

| Method | URI | Purpose | Parameters |
|--------|-----|---------|------------|
| GET | `/` | Today's articles | `?folder={slug}` (optional filter) |
| GET | `/date/{date}` | Articles for a specific date | `{date}` = Y-m-d, `?folder={slug}` (optional) |
| GET | `/article/{id}` | Single article JSON (for modal) | `{id}` = article ID |

### 4.2 Response Shapes

**Article JSON** (for modal):
```json
{
    "id": 1,
    "title": "Article Title",
    "url": "https://example.com/article",
    "content": "<p>Full HTML content...</p>",
    "author": "John Doe",
    "published_at": "2026-05-04T10:30:00Z",
    "cover_image": "https://example.com/image.jpg",
    "feed": {
        "id": 1,
        "title": "Example Blog",
        "site_url": "https://example.com"
    }
}
```

## 5. File Structure

New files to create:

```
app/
├── Console/
│   └── Commands/
│       ├── FeedAddCommand.php          # rss:feed:add
│       ├── FeedRemoveCommand.php       # rss:feed:remove
│       ├── FeedListCommand.php         # rss:feed:list
│       ├── FeedInfoCommand.php         # rss:feed:info
│       ├── FetchFeedsCommand.php       # rss:fetch
│       ├── FolderCreateCommand.php     # rss:folder:create
│       ├── FolderDeleteCommand.php     # rss:folder:delete
│       ├── FolderMoveCommand.php       # rss:folder:move
│       └── FolderListCommand.php       # rss:folder:list
├── Http/
│   └── Controllers/
│       └── ArticleController.php       # Public reading UI
├── Models/
│   ├── Article.php                     # Article Eloquent model
│   ├── Feed.php                        # Feed Eloquent model
│   └── Folder.php                      # Folder Eloquent model
├── Services/
│   └── FeedParser.php                  # RSS/Atom parsing service
database/
├── factories/
│   ├── ArticleFactory.php
│   ├── FeedFactory.php
│   └── FolderFactory.php
├── migrations/
│   ├── xxxx_create_folders_table.php
│   ├── xxxx_create_feeds_table.php
│   └── xxxx_create_articles_table.php
resources/
├── js/
│   └── app.js                          # Modal JS + Alpine-style vanilla
├── views/
│   ├── layouts/
│   │   └── app.blade.php               # Base layout (head, header, footer)
│   ├── articles/
│   │   └── index.blade.php             # Main article list view
│   └── partials/
│       ├── article-card.blade.php      # Single article card
│       ├── date-navigation.blade.php   # Date picker + prev/next
│       └── folder-filter.blade.php     # Folder pill/chip buttons
routes/
├── web.php                             # Public routes
└── console.php                         # Scheduled fetch
tests/
├── Feature/
│   ├── ArticleControllerTest.php
│   ├── FeedAddCommandTest.php
│   ├── FetchFeedsCommandTest.php
│   └── FolderCreateCommandTest.php
└── Unit/
    └── FeedParserTest.php
```

### Naming Conventions

- **Commands**: PascalCase + `Command` suffix, stored in `app/Console/Commands/`
- **Models**: Singular PascalCase, stored in `app/Models/`
- **Migrations**: Snake_case with descriptive name
- **Views**: Kebab-case Blade files, organized by resource
- **Routes**: Kebab-case URL segments, named routes

## 6. Design Decisions

### Decision 1: PHP SimpleXML for RSS/Atom parsing
- **Choice**: Use built-in `SimpleXML` with `libxml_use_internal_errors` for error handling
- **Because**: No external dependencies needed; SimpleXML handles well-formed XML feeds; for malformed feeds we catch errors and report them gracefully
- **Fallback**: Use `DOMDocument` for feeds that SimpleXML can't handle (more forgiving parser)

### Decision 2: Feed auto-detection strategy
- **Choice**: Fetch URL, check `Content-Type` header first, then inspect XML root element
- **Because**: Some feeds are at non-obvious URLs; checking both header and content ensures we detect RSS vs Atom reliably

### Decision 3: Article deduplication via external_id
- **Choice**: Use `<guid>` (RSS) or `<id>` (Atom) as the unique identifier, fallback to URL
- **Because**: Articles may be updated after publishing (content corrections); guid/id is the canonical identifier per RSS/Atom specs; unique constraint on `(external_id, feed_id)` prevents duplicates

### Decision 4: Single column layout with server-rendered Blade
- **Choice**: Blade templates with Tailwind, no JavaScript framework
- **Because**: Minimal interactivity (just modal); server-rendered is faster and simpler; PWA caching works with static HTML

### Decision 5: Vanilla JS modal instead of Alpine/Livewire
- **Choice**: Simple fetch → inject into modal overlay, vanilla JS
- **Because**: Only one interactive element (article modal); adding Alpine or Livewire is overkill for a single modal; keeps JS bundle minimal

### Decision 6: Date-based URL structure
- **Choice**: `/` for today, `/date/{Y-m-d}` for specific dates
- **Because**: Clean, RESTful, cacheable URLs; no query params for the primary dimension (date); folder filtering via query param is secondary and optional

## 7. Security Considerations

- **Feed URL validation**: Validate URL format before HTTP request; reject non-HTTP(S) schemes
- **HTML sanitization**: Article content from feeds is untrusted HTML. Use a sanitization approach:
  - Strip `<script>`, `<iframe>`, `<object>`, `<embed>`, `<form>` tags from article content before rendering
  - Strip `on*` event attributes (onclick, onload, etc.)
  - Allow safe HTML tags for formatting (p, h1-h6, a, img, ul, ol, li, blockquote, pre, code, strong, em)
- **No user auth needed**: Public read-only; CLI is the admin interface (server access required)
- **Rate limiting**: Apply `throttle:60,1` middleware to public routes
- **Input validation**: Date route parameter validated as `Y-m-d` format; folder slug validated as alphanumeric + dashes
- **SQL injection**: Eloquent ORM with parameterized queries — no raw SQL

## 8. Risks & Mitigations

| Risk | Impact | Mitigation |
|------|--------|------------|
| Malformed XML in feeds | Parse failures, missed articles | Wrap parsing in try/catch; log errors with feed name; skip to next feed |
| Feed content with malicious HTML | XSS in the reader | Sanitize HTML before storage; strip dangerous tags and attributes |
| Large feed responses | Memory issues during fetch | Set HTTP timeout (30s); stream response body; limit article storage per fetch |
| SQLite concurrency | Locked database during writes | Single fetch process (scheduled command); no concurrent writes expected |
| Cover image extraction varies by feed | Missing images | Support multiple extraction strategies: `<enclosure>`, `<media:content>`, `<media:thumbnail>`, og:image from content |
| Feeds with no dates | Articles can't be filtered by date | Use current time as fallback `published_at`; log a warning |
