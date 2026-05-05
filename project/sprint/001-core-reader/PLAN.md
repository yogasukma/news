# Sprint 001: Core Reader

## Sprint Goal
Build the complete core loop: create folders and feeds via CLI, fetch and parse RSS/Atom articles, and display them in a public daily-digest web interface with date navigation and folder filtering.

## Duration
2026-05-04 → TBD

## Selected Stories
| Story | Title | Points | Priority |
|-------|-------|--------|----------|
| US-005 | Create a folder | 2 | P0 |
| US-001 | Subscribe to a feed by URL | 5 | P0 |
| US-009 | Parse and store articles from RSS 2.0 | 8 | P0 |
| US-010 | Parse and store articles from Atom | 5 | P0 |
| US-011 | Fetch all feeds command | 5 | P0 |
| US-013 | Handle feed errors gracefully | 3 | P0 |
| US-014 | Schedule automatic fetching | 2 | P0 |
| US-015 | Today's feeds homepage | 5 | P0 |
| US-016 | Date navigation | 3 | P0 |
| US-017 | Category/folder filter | 3 | P0 |
| US-018 | Article card display | 3 | P0 |
| US-019 | Article reading view | 3 | P0 |

## Sprint Capacity
- Total story points: 47
- Number of stories: 12

---

## Task Breakdown

### US-005: Create a folder
- [ ] Task 1: Create `folders` migration (id, name, slug, timestamps)
- [ ] Task 2: Create `Folder` model with fillable fields and relationships
- [ ] Task 3: Create `rss:folder:create` Artisan command with duplicate name check
- [ ] Task 4: Write tests for folder creation and duplicate handling

### US-001: Subscribe to a feed by URL
- [ ] Task 1: Create `feeds` migration (id, title, url, site_url, description, favicon_url, folder_id nullable, last_fetched_at nullable, timestamps)
- [ ] Task 2: Create `Feed` model with folder relationship and fillable fields
- [ ] Task 3: Create `rss:feed:add {url}` Artisan command — fetch URL, auto-detect RSS/Atom, parse metadata, store feed
- [ ] Task 4: Handle duplicate feed URL detection with clear message
- [ ] Task 5: Handle invalid URLs and non-feed URLs with clear error messages
- [ ] Task 6: Write tests for feed subscription, duplicates, and invalid URLs

### US-009: Parse and store articles from RSS 2.0 feeds
- [ ] Task 1: Create `articles` migration (id, feed_id, title, url, content, author, published_at, cover_image nullable, external_id nullable, timestamps)
- [ ] Task 2: Create `Article` model with feed relationship and fillable fields
- [ ] Task 3: Build `FeedParser` service class — RSS 2.0 parser using PHP's SimpleXML
- [ ] Task 4: Extract article data: title, URL, content/body, author, published date, cover image (from media:content or enclosure)
- [ ] Task 5: Implement deduplication using external_id (guid) or URL
- [ ] Task 6: Write tests for RSS 2.0 parsing with sample feed XML

### US-010: Parse and store articles from Atom feeds
- [ ] Task 1: Extend `FeedParser` service to handle Atom format (entry elements, href links, content elements)
- [ ] Task 2: Handle Atom-specific fields (updated vs published, link href attribute, content type)
- [ ] Task 3: Write tests for Atom parsing with sample feed XML

### US-011: Fetch all feeds command
- [ ] Task 1: Create `rss:fetch` Artisan command — iterate all feeds, call FeedParser, store articles
- [ ] Task 2: Display summary output (feeds fetched, new articles, errors) using Laravel Prompts or console tables
- [ ] Task 3: Update feed's `last_fetched_at` after successful fetch
- [ ] Task 4: Write tests for the fetch command

### US-013: Handle feed errors gracefully
- [ ] Task 1: Add HTTP timeout handling (configurable, default 30s) to feed fetching
- [ ] Task 2: Wrap each feed's fetch+parse in try/catch, log errors with feed name and URL
- [ ] Task 3: Ensure one feed failure doesn't stop the batch — continue to next feed
- [ ] Task 4: Write tests for timeout, invalid XML, and 404 scenarios

### US-014: Schedule automatic fetching
- [ ] Task 1: Register `rss:fetch` hourly in the Laravel scheduler (`routes/console.php`)
- [ ] Task 2: Write test to verify schedule is registered

### US-015: Today's feeds homepage
- [ ] Task 1: Create `ArticleController` with `index` method — query articles published today
- [ ] Task 2: Create route `GET /` mapping to controller
- [ ] Task 3: Create Blade layout with single column design, clean typography, Tailwind CSS
- [ ] Task 4: Create article list Blade view showing today's articles
- [ ] Task 5: Handle empty state ("no articles yet today" message)
- [ ] Task 6: Write feature tests for the homepage route

### US-016: Date navigation
- [ ] Task 1: Add `GET /date/{date}` route with date validation (Y-m-d format)
- [ ] Task 2: Add previous day / next day navigation controls to the view
- [ ] Task 3: Add date picker input for jumping to a specific date
- [ ] Task 4: Handle "next day" not going beyond today
- [ ] Task 5: Handle empty state for dates with no articles
- [ ] Task 6: Write feature tests for date navigation

### US-017: Category/folder filter
- [ ] Task 1: Add optional `folder` query parameter to routes for filtering by folder
- [ ] Task 2: Load folders list for filter controls in the view
- [ ] Task 3: Create pill/chip filter buttons (All + each folder name) above article list
- [ ] Task 4: Style active filter state
- [ ] Task 5: Write feature tests for folder filtering

### US-018: Article card display
- [ ] Task 1: Design article card Blade component — title, feed name, published time, excerpt, cover image
- [ ] Task 2: Style cards with Tailwind — clean, readable, single column
- [ ] Task 3: Generate text excerpt from article content (strip HTML, truncate)
- [ ] Task 4: Write tests for card rendering

### US-019: Article reading view
- [ ] Task 1: Create `GET /article/{article}` route that returns article data as JSON for modal
- [ ] Task 2: Build modal component with clean typography for full article content
- [ ] Task 3: Add close on Escape key and click outside modal
- [ ] Task 4: Add "Read original" link opening source URL in new tab
- [ ] Task 5: Style modal with Tailwind (centered, max-width, scrollable)
- [ ] Task 6: Write tests for article modal route
