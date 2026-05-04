# Product Backlog

## Summary
- Total stories: 25
- Total story points: 99
- P0 (Critical): 13 stories
- P1 (High): 6 stories
- P2 (Medium): 4 stories
- P3 (Low): 2 stories

---

## Module: Feed Management (CLI)

### US-001: Subscribe to a feed by URL
- **As a** site owner, **I want** to add an RSS/Atom feed by URL via `rss:feed:add {url}`, **so that** I can subscribe to new content sources.
- **Priority**: P0
- **Points**: 5
- **Dependencies**: None
- **Acceptance Criteria**:
  - [ ] Given a valid RSS 2.0 feed URL, When I run `rss:feed:add https://example.com/feed`, Then the feed is created in the database with title, URL, and site URL
  - [ ] Given a valid Atom feed URL, When I run the command, Then the feed is created successfully
  - [ ] Given an invalid URL (not a feed), When I run the command, Then a clear error message is displayed and no feed is created
  - [ ] Given a feed URL already subscribed, When I run the command, Then a "already subscribed" message is shown
  - [ ] Given the feed has a favicon, When the feed is added, Then the favicon URL is stored

### US-002: Unsubscribe from a feed
- **As a** site owner, **I want** to remove a feed via `rss:feed:remove {feed}`, **so that** I can stop tracking unwanted sources.
- **Priority**: P1
- **Points**: 2
- **Dependencies**: US-001
- **Acceptance Criteria**:
  - [ ] Given an existing feed ID, When I run `rss:feed:remove 1`, Then the feed and all its articles are deleted
  - [ ] Given a non-existent feed ID, When I run the command, Then a "feed not found" error is shown
  - [ ] Given an existing feed, When I run the command with confirmation, Then the feed is removed with a success message showing feed name

### US-003: List all feeds
- **As a** site owner, **I want** to list all subscribed feeds via `rss:feed:list`, **so that** I can see what I'm subscribed to.
- **Priority**: P1
- **Points**: 2
- **Dependencies**: US-001
- **Acceptance Criteria**:
  - [ ] Given existing feeds, When I run `rss:feed:list`, Then a table is displayed showing ID, title, URL, folder, article count, and last fetched date
  - [ ] Given no feeds, When I run the command, Then a "no feeds found" message is shown

### US-004: Show feed details
- **As a** site owner, **I want** to see details of a specific feed via `rss:feed:info {feed}`, **so that** I can inspect a feed's status.
- **Priority**: P2
- **Points**: 2
- **Dependencies**: US-001
- **Acceptance Criteria**:
  - [ ] Given an existing feed ID, When I run `rss:feed:info 1`, Then detailed info is shown (title, URL, site URL, folder, article count, last fetched, created date)
  - [ ] Given a non-existent feed ID, When I run the command, Then a "feed not found" error is shown

### US-005: Create a folder
- **As a** site owner, **I want** to create a folder via `rss:folder:create {name}`, **so that** I can organize feeds into groups.
- **Priority**: P0
- **Points**: 2
- **Dependencies**: None
- **Acceptance Criteria**:
  - [ ] Given a unique name, When I run `rss:folder:create Tech`, Then the folder is created with a success message
  - [ ] Given a duplicate name, When I run the command, Then a "folder already exists" error is shown

### US-006: Delete a folder
- **As a** site owner, **I want** to delete a folder via `rss:folder:delete {folder}`, **so that** I can remove unused groups.
- **Priority**: P2
- **Points**: 2
- **Dependencies**: US-005
- **Acceptance Criteria**:
  - [ ] Given an existing folder ID, When I run `rss:folder:delete 1`, Then the folder is deleted and its feeds are moved to "uncategorized"
  - [ ] Given a non-existent folder ID, When I run the command, Then a "folder not found" error is shown

### US-007: Move a feed into a folder
- **As a** site owner, **I want** to assign a feed to a folder via `rss:folder:move {feed} {folder}`, **so that** feeds are organized into categories.
- **Priority**: P1
- **Points**: 2
- **Dependencies**: US-001, US-005
- **Acceptance Criteria**:
  - [ ] Given a valid feed ID and folder ID, When I run `rss:folder:move 1 2`, Then the feed is assigned to the folder
  - [ ] Given an invalid feed ID, When I run the command, Then a "feed not found" error is shown
  - [ ] Given an invalid folder ID, When I run the command, Then a "folder not found" error is shown

### US-008: List all folders
- **As a** site owner, **I want** to list all folders via `rss:folder:list`, **so that** I can see my feed organization.
- **Priority**: P2
- **Points**: 2
- **Dependencies**: US-005
- **Acceptance Criteria**:
  - [ ] Given existing folders, When I run `rss:folder:list`, Then a table is displayed showing ID, name, and number of feeds per folder
  - [ ] Given no folders, When I run the command, Then a "no folders found" message is shown

---

## Module: Feed Fetching

### US-009: Parse and store articles from RSS 2.0 feeds
- **As a** site owner, **I want** the fetcher to parse RSS 2.0 feeds and store articles, **so that** new content is available in the reader.
- **Priority**: P0
- **Points**: 8
- **Dependencies**: US-001
- **Acceptance Criteria**:
  - [ ] Given a feed with new RSS 2.0 articles, When fetched, Then each article is stored with title, URL, content/body, author, published date, and cover image
  - [ ] Given a previously fetched article, When the same article is encountered again, Then it is skipped / ignored. 
  - [ ] Given an article with no explicit published date, When stored, Then the current time is used as fallback

### US-010: Parse and store articles from Atom feeds
- **As a** site owner, **I want** the fetcher to parse Atom feeds and store articles, **so that** Atom-format sources work alongside RSS.
- **Priority**: P0
- **Points**: 5
- **Dependencies**: US-001
- **Acceptance Criteria**:
  - [ ] Given a feed with new Atom entries, When fetched, Then each entry is stored with title, URL, content, author, published date, and cover image
  - [ ] Given a previously fetched entry, When encountered again, Then it is updated (not duplicated)

### US-011: Fetch all feeds command
- **As a** site owner, **I want** to run `rss:fetch` to fetch all feeds, **so that** articles are updated on schedule.
- **Priority**: P0
- **Points**: 5
- **Dependencies**: US-009, US-010
- **Acceptance Criteria**:
  - [ ] Given multiple feeds, When I run `rss:fetch`, Then all feeds are fetched sequentially and a summary is displayed (total fetched, new articles, errors)
  - [ ] Given a feed that returns an error, When fetching all, Then the error is logged and other feeds continue fetching
  - [ ] Given no feeds, When I run the command, Then a "no feeds to fetch" message is shown

### US-012: Fetch single feed command
- **As a** site owner, **I want** to run `rss:fetch {feed}` to fetch one feed, **so that** I can manually refresh a specific source.
- **Priority**: P1
- **Points**: 3
- **Dependencies**: US-009, US-010
- **Acceptance Criteria**:
  - [ ] Given an existing feed ID, When I run `rss:fetch 1`, Then only that feed is fetched and results are shown
  - [ ] Given a non-existent feed ID, When I run the command, Then a "feed not found" error is shown

### US-013: Handle feed errors gracefully
- **As a** site owner, **I want** feed fetching to handle errors without crashing, **so that** one bad feed doesn't stop the whole process.
- **Priority**: P0
- **Points**: 3
- **Dependencies**: US-009
- **Acceptance Criteria**:
  - [ ] Given a feed URL that times out, When fetched, Then a timeout error is logged and fetching continues to the next feed
  - [ ] Given a feed that returns invalid XML, When fetched, Then a parse error is logged with the feed name
  - [ ] Given a feed URL that returns 404, When fetched, Then a "feed not found" error is logged

### US-014: Schedule automatic fetching
- **As a** site owner, **I want** feed fetching to run automatically on a schedule, **so that** articles are always up to date.
- **Priority**: P0
- **Points**: 2
- **Dependencies**: US-011
- **Acceptance Criteria**:
  - [ ] Given the scheduler is running, Then `rss:fetch` runs every hour automatically
  - [ ] Given the schedule is configured, Then it is registered in the Laravel scheduler (routes/console.php or console kernel)

---

## Module: Article Reading (Public Web UI)

### US-015: Today's feeds homepage
- **As a** public visitor, **I want** to see all articles published today on the homepage, **so that** I can quickly see what's new.
- **Priority**: P0
- **Points**: 5
- **Dependencies**: US-009
- **Acceptance Criteria**:
  - [ ] Given articles published today, When I visit the homepage, Then all today's articles are displayed in a single column layout
  - [ ] Given no articles published today, When I visit the homepage, Then a "no articles yet today" message is shown
  - [ ] Given articles from multiple feeds, When displayed, Then each article card shows title, feed name, published time, and excerpt

### US-016: Date navigation
- **As a** public visitor, **I want** to navigate between dates, **so that** I can browse articles from previous days.
- **Priority**: P0
- **Points**: 3
- **Dependencies**: US-015
- **Acceptance Criteria**:
  - [ ] Given I am on today's page, When I click "yesterday" or previous day control, Then articles from the previous date are shown
  - [ ] Given I am on a past date, When I click "next day", Then the next date's articles are shown (up to today)
  - [ ] Given I pick a specific date via date picker, Then articles from that date are shown
  - [ ] Given a date with no articles, When navigated to, Then a "no articles on this date" message is shown

### US-017: Category/folder filter
- **As a** public visitor, **I want** to filter articles by folder/category, **so that** I can focus on specific topics.
- **Priority**: P0
- **Points**: 3
- **Dependencies**: US-015, US-005
- **Acceptance Criteria**:
  - [ ] Given articles from multiple folders on a date, When I select a folder filter, Then only articles from feeds in that folder are shown
  - [ ] Given the "all" filter, When selected, Then articles from all folders are shown
  - [ ] Given folder filter controls, Then they are displayed as minimal controls (e.g., pill/chip buttons) above the article list

### US-018: Article card display
- **As a** public visitor, **I want** each article to show key info in a card, **so that** I can quickly scan and decide what to read.
- **Priority**: P0
- **Points**: 3
- **Dependencies**: US-015
- **Acceptance Criteria**:
  - [ ] Given an article, When displayed as a card, Then it shows title, source feed name, published time, and a text excerpt
  - [ ] Given an article with a cover image, When displayed, Then the cover image is shown in the card
  - [ ] Given article cards in a list, When I click a card, Then the full article content is shown (in a modal or expanded view)

### US-019: Article reading view
- **As a** public visitor, **I want** to read the full article content in a clean modal, **so that** I can read without leaving the page.
- **Priority**: P0
- **Points**: 3
- **Dependencies**: US-018
- **Acceptance Criteria**:
  - [ ] Given an article card, When clicked, Then a modal opens showing the full article content with clean typography
  - [ ] Given the modal is open, When I click close or press Escape, Then the modal closes and returns to the article list
  - [ ] Given the modal, Then a "read original" link is displayed that opens the source URL in a new tab

---

## Module: OPML Import/Export (CLI)

### US-020: Import OPML file
- **As a** site owner, **I want** to import feeds from an OPML file via `rss:opml:import {file}`, **so that** I can migrate from another reader.
- **Priority**: P1
- **Points**: 5
- **Dependencies**: US-001, US-005
- **Acceptance Criteria**:
  - [ ] Given a valid OPML file with feeds and folders, When I run `rss:opml:import subs.xml`, Then all feeds and folders are created
  - [ ] Given an OPML file with duplicate feeds (already subscribed), When imported, Then duplicates are skipped and reported
  - [ ] Given an invalid OPML file, When imported, Then a clear error message is shown
  - [ ] Given a successful import, When complete, Then a summary is displayed (added, skipped, errors)

### US-021: Export OPML file
- **As a** site owner, **I want** to export feeds to an OPML file via `rss:opml:export {file}`, **so that** I can backup or migrate subscriptions.
- **Priority**: P2
- **Points**: 3
- **Dependencies**: US-001
- **Acceptance Criteria**:
  - [ ] Given existing feeds and folders, When I run `rss:opml:export backup.xml`, Then a valid OPML file is created with all feeds organized by folder
  - [ ] Given no feeds, When I run the command, Then an OPML file with empty body is created
  - [ ] Given the exported file, When opened in another reader, Then all feeds and folders are recognized correctly

---

## Module: PWA Support

### US-022: Web app manifest and icons
- **As a** public visitor, **I want** to install the app on my device, **so that** I can access it like a native app.
- **Priority**: P1
- **Points**: 2
- **Dependencies**: US-015
- **Acceptance Criteria**:
  - [ ] Given the app is loaded, Then a valid web app manifest is served with name, icons, theme color, and display mode
  - [ ] Given the manifest, Then app icons for required sizes (192x192, 512x512) are available
  - [ ] Given a mobile browser, Then the browser shows an "install" prompt for the PWA

### US-023: Service worker for offline caching
- **As a** public visitor, **I want** the app to cache its shell for offline access, **so that** it loads quickly on return visits.
- **Priority**: P2
- **Points**: 3
- **Dependencies**: US-022
- **Acceptance Criteria**:
  - [ ] Given the app is loaded, Then a service worker is registered and caches the app shell (HTML, CSS, JS)
  - [ ] Given the app is cached, When I revisit offline, Then the app shell loads from cache
  - [ ] Given a new version is deployed, When online, Then the service worker updates the cache

---

## Module: Search

### US-024: Full-text search
- **As a** public visitor, **I want** to search across all articles by keyword, **so that** I can find specific content.
- **Priority**: P3
- **Points**: 5
- **Dependencies**: US-015
- **Acceptance Criteria**:
  - [ ] Given a search query, When I submit the search, Then articles matching the query in title or content are shown
  - [ ] Given search results, Then each result shows title, feed name, published date, and a highlighted excerpt
  - [ ] Given no matching articles, When searched, Then a "no results found" message is shown

### US-025: Search with date and folder filters
- **As a** public visitor, **I want** to combine search with date and folder filters, **so that** I can narrow down results.
- **Priority**: P3
- **Points**: 3
- **Dependencies**: US-024, US-016, US-017
- **Acceptance Criteria**:
  - [ ] Given a search within a date range, When results are shown, Then only articles within that range appear
  - [ ] Given a search within a folder, When results are shown, Then only articles from that folder appear
