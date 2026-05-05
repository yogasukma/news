# Product Backlog

## Summary
- Total stories: 37
- Delivered: 37 (Sprint 001: 12, Sprint 002: 9, Sprint 003: 4, Sprint 004: 3, Sprint 005: 4, Sprint 006: 2, Sprint 007: 2, Sprint 008: 1)
- Remaining: 0
- Total story points: 139
- Delivered points: 139
- Remaining points: 0

---

## Module: Feed Management (CLI)

### US-001: [DELIVERED] Subscribe to a feed by URL
- **As a** site owner, **I want** to add an RSS/Atom feed by URL via `rss:feed:add {url}`, **so that** I can subscribe to new content sources.
- **Priority**: P0
- **Points**: 5
- **Dependencies**: None
- **Status**: Delivered in Sprint 001
- **Acceptance Criteria**:
  - [ ] Given a valid RSS 2.0 feed URL, When I run `rss:feed:add https://example.com/feed`, Then the feed is created in the database with title, URL, and site URL
  - [ ] Given a valid Atom feed URL, When I run the command, Then the feed is created successfully
  - [ ] Given an invalid URL (not a feed), When I run the command, Then a clear error message is displayed and no feed is created
  - [ ] Given a feed URL already subscribed, When I run the command, Then a "already subscribed" message is shown
  - [ ] Given the feed has a favicon, When the feed is added, Then the favicon URL is stored

### US-002: [DELIVERED] Unsubscribe from a feed
- **As a** site owner, **I want** to remove a feed via `rss:feed:remove {feed}`, **so that** I can stop tracking unwanted sources.
- **Priority**: P1
- **Points**: 2
- **Dependencies**: US-001
- **Status**: Delivered in Sprint 002
- **Acceptance Criteria**:
  - [ ] Given an existing feed ID, When I run `rss:feed:remove 1`, Then the feed and all its articles are deleted
  - [ ] Given a non-existent feed ID, When I run the command, Then a "feed not found" error is shown
  - [ ] Given an existing feed, When I run the command with confirmation, Then the feed is removed with a success message showing feed name

### US-003: [DELIVERED] List all feeds
- **As a** site owner, **I want** to list all subscribed feeds via `rss:feed:list`, **so that** I can see what I'm subscribed to.
- **Priority**: P1
- **Points**: 2
- **Dependencies**: US-001
- **Status**: Delivered in Sprint 002
- **Acceptance Criteria**:
  - [ ] Given existing feeds, When I run `rss:feed:list`, Then a table is displayed showing ID, title, URL, folder, article count, and last fetched date
  - [ ] Given no feeds, When I run the command, Then a "no feeds found" message is shown

### US-004: [DELIVERED] Show feed details
- **As a** site owner, **I want** to see details of a specific feed via `rss:feed:info {feed}`, **so that** I can inspect a feed's status.
- **Priority**: P2
- **Points**: 2
- **Dependencies**: US-001
- **Status**: Delivered in Sprint 002
- **Acceptance Criteria**:
  - [ ] Given an existing feed ID, When I run `rss:feed:info 1`, Then detailed info is shown (title, URL, site URL, folder, article count, last fetched, created date)
  - [ ] Given a non-existent feed ID, When I run the command, Then a "feed not found" error is shown

### US-005: [DELIVERED] Create a folder
- **As a** site owner, **I want** to create a folder via `rss:folder:create {name}`, **so that** I can organize feeds into groups.
- **Priority**: P0
- **Points**: 2
- **Dependencies**: None
- **Status**: Delivered in Sprint 001
- **Acceptance Criteria**:
  - [ ] Given a unique name, When I run `rss:folder:create Tech`, Then the folder is created with a success message
  - [ ] Given a duplicate name, When I run the command, Then a "folder already exists" error is shown

### US-006: [DELIVERED] Delete a folder
- **As a** site owner, **I want** to delete a folder via `rss:folder:delete {folder}`, **so that** I can remove unused groups.
- **Priority**: P2
- **Points**: 2
- **Dependencies**: US-005
- **Status**: Delivered in Sprint 002
- **Acceptance Criteria**:
  - [ ] Given an existing folder ID, When I run `rss:folder:delete 1`, Then the folder is deleted and its feeds are moved to "uncategorized"
  - [ ] Given a non-existent folder ID, When I run the command, Then a "folder not found" error is shown

### US-007: [DELIVERED] Move a feed into a folder
- **As a** site owner, **I want** to assign a feed to a folder via `rss:folder:move {feed} {folder}`, **so that** feeds are organized into categories.
- **Priority**: P1
- **Points**: 2
- **Dependencies**: US-001, US-005
- **Status**: Delivered in Sprint 002
- **Acceptance Criteria**:
  - [ ] Given a valid feed ID and folder ID, When I run `rss:folder:move 1 2`, Then the feed is assigned to the folder
  - [ ] Given an invalid feed ID, When I run the command, Then a "feed not found" error is shown
  - [ ] Given an invalid folder ID, When I run the command, Then a "folder not found" error is shown

### US-008: [DELIVERED] List all folders
- **As a** site owner, **I want** to list all folders via `rss:folder:list`, **so that** I can see my feed organization.
- **Priority**: P2
- **Points**: 2
- **Dependencies**: US-005
- **Status**: Delivered in Sprint 002
- **Acceptance Criteria**:
  - [ ] Given existing folders, When I run `rss:folder:list`, Then a table is displayed showing ID, name, and number of feeds per folder
  - [ ] Given no folders, When I run the command, Then a "no folders found" message is shown

---

## Module: Feed Fetching

### US-009: [DELIVERED] Parse and store articles from RSS 2.0 feeds
- **As a** site owner, **I want** the fetcher to parse RSS 2.0 feeds and store articles, **so that** new content is available in the reader.
- **Priority**: P0
- **Points**: 8
- **Dependencies**: US-001
- **Status**: Delivered in Sprint 001
- **Acceptance Criteria**:
  - [ ] Given a feed with new RSS 2.0 articles, When fetched, Then each article is stored with title, URL, content/body, author, published date, and cover image
  - [ ] Given a previously fetched article, When the same article is encountered again, Then it is skipped / ignored. 
  - [ ] Given an article with no explicit published date, When stored, Then the current time is used as fallback

### US-010: [DELIVERED] Parse and store articles from Atom feeds
- **As a** site owner, **I want** the fetcher to parse Atom feeds and store articles, **so that** Atom-format sources work alongside RSS.
- **Priority**: P0
- **Points**: 5
- **Dependencies**: US-001
- **Status**: Delivered in Sprint 001
- **Acceptance Criteria**:
  - [ ] Given a feed with new Atom entries, When fetched, Then each entry is stored with title, URL, content, author, published date, and cover image
  - [ ] Given a previously fetched entry, When encountered again, Then it is updated (not duplicated)

### US-011: [DELIVERED] Fetch all feeds command
- **As a** site owner, **I want** to run `rss:fetch` to fetch all feeds, **so that** articles are updated on schedule.
- **Priority**: P0
- **Points**: 5
- **Dependencies**: US-009, US-010
- **Status**: Delivered in Sprint 001
- **Acceptance Criteria**:
  - [ ] Given multiple feeds, When I run `rss:fetch`, Then all feeds are fetched sequentially and a summary is displayed (total fetched, new articles, errors)
  - [ ] Given a feed that returns an error, When fetching all, Then the error is logged and other feeds continue fetching
  - [ ] Given no feeds, When I run the command, Then a "no feeds to fetch" message is shown

### US-012: [DELIVERED] Fetch single feed command
- **As a** site owner, **I want** to run `rss:fetch {feed}` to fetch one feed, **so that** I can manually refresh a specific source.
- **Priority**: P1
- **Points**: 3
- **Dependencies**: US-009, US-010
- **Status**: Delivered in Sprint 002
- **Acceptance Criteria**:
  - [ ] Given an existing feed ID, When I run `rss:fetch 1`, Then only that feed is fetched and results are shown
  - [ ] Given a non-existent feed ID, When I run the command, Then a "feed not found" error is shown

### US-013: [DELIVERED] Handle feed errors gracefully
- **As a** site owner, **I want** feed fetching to handle errors without crashing, **so that** one bad feed doesn't stop the whole process.
- **Priority**: P0
- **Points**: 3
- **Dependencies**: US-009
- **Status**: Delivered in Sprint 001
- **Acceptance Criteria**:
  - [ ] Given a feed URL that times out, When fetched, Then a timeout error is logged and fetching continues to the next feed
  - [ ] Given a feed that returns invalid XML, When fetched, Then a parse error is logged with the feed name
  - [ ] Given a feed URL that returns 404, When fetched, Then a "feed not found" error is logged

### US-014: [DELIVERED] Schedule automatic fetching
- **As a** site owner, **I want** feed fetching to run automatically on a schedule, **so that** articles are always up to date.
- **Priority**: P0
- **Points**: 2
- **Dependencies**: US-011
- **Status**: Delivered in Sprint 001
- **Acceptance Criteria**:
  - [ ] Given the scheduler is running, Then `rss:fetch` runs every hour automatically
  - [ ] Given the schedule is configured, Then it is registered in the Laravel scheduler (routes/console.php or console kernel)

---

## Module: Article Reading (Public Web UI)

### US-015: [DELIVERED] Today's feeds homepage
- **As a** public visitor, **I want** to see all articles published today on the homepage, **so that** I can quickly see what's new.
- **Priority**: P0
- **Points**: 5
- **Dependencies**: US-009
- **Status**: Delivered in Sprint 001
- **Acceptance Criteria**:
  - [ ] Given articles published today, When I visit the homepage, Then all today's articles are displayed in a single column layout
  - [ ] Given no articles published today, When I visit the homepage, Then a "no articles yet today" message is shown
  - [ ] Given articles from multiple feeds, When displayed, Then each article card shows title, feed name, published time, and excerpt

### US-016: [DELIVERED] Date navigation
- **As a** public visitor, **I want** to navigate between dates, **so that** I can browse articles from previous days.
- **Priority**: P0
- **Points**: 3
- **Dependencies**: US-015
- **Status**: Delivered in Sprint 001
- **Acceptance Criteria**:
  - [ ] Given I am on today's page, When I click "yesterday" or previous day control, Then articles from the previous date are shown
  - [ ] Given I am on a past date, When I click "next day", Then the next date's articles are shown (up to today)
  - [ ] Given I pick a specific date via date picker, Then articles from that date are shown
  - [ ] Given a date with no articles, When navigated to, Then a "no articles on this date" message is shown

### US-017: [DELIVERED] Category/folder filter
- **As a** public visitor, **I want** to filter articles by folder/category, **so that** I can focus on specific topics.
- **Priority**: P0
- **Points**: 3
- **Dependencies**: US-015, US-005
- **Status**: Delivered in Sprint 001
- **Acceptance Criteria**:
  - [ ] Given articles from multiple folders on a date, When I select a folder filter, Then only articles from feeds in that folder are shown
  - [ ] Given the "all" filter, When selected, Then articles from all folders are shown
  - [ ] Given folder filter controls, Then they are displayed as minimal controls (e.g., pill/chip buttons) above the article list

### US-018: [DELIVERED] Article card display
- **As a** public visitor, **I want** each article to show key info in a card, **so that** I can quickly scan and decide what to read.
- **Priority**: P0
- **Points**: 3
- **Dependencies**: US-015
- **Status**: Delivered in Sprint 001
- **Acceptance Criteria**:
  - [ ] Given an article, When displayed as a card, Then it shows title, source feed name, published time, and a text excerpt
  - [ ] Given an article with a cover image, When displayed, Then the cover image is shown in the card
  - [ ] Given article cards in a list, When I click a card, Then the full article content is shown (in a modal or expanded view)

### US-019: [DELIVERED] Article reading view
- **As a** public visitor, **I want** to read the full article content in a clean modal, **so that** I can read without leaving the page.
- **Priority**: P0
- **Points**: 3
- **Dependencies**: US-018
- **Status**: Delivered in Sprint 001
- **Acceptance Criteria**:
  - [ ] Given an article card, When clicked, Then a modal opens showing the full article content with clean typography
  - [ ] Given the modal is open, When I click close or press Escape, Then the modal closes and returns to the article list
  - [ ] Given the modal, Then a "read original" link is displayed that opens the source URL in a new tab

---

## Module: OPML Import/Export (CLI)

### US-020: [DELIVERED] Import OPML file
- **As a** site owner, **I want** to import feeds from an OPML file via `rss:opml:import {file}`, **so that** I can migrate from another reader.
- **Priority**: P1
- **Points**: 5
- **Dependencies**: US-001, US-005
- **Status**: Delivered in Sprint 002
- **Acceptance Criteria**:
  - [ ] Given a valid OPML file with feeds and folders, When I run `rss:opml:import subs.xml`, Then all feeds and folders are created
  - [ ] Given an OPML file with duplicate feeds (already subscribed), When imported, Then duplicates are skipped and reported
  - [ ] Given an invalid OPML file, When imported, Then a clear error message is shown
  - [ ] Given a successful import, When complete, Then a summary is displayed (added, skipped, errors)

### US-021: [DELIVERED] Export OPML file
- **As a** site owner, **I want** to export feeds to an OPML file via `rss:opml:export {file}`, **so that** I can backup or migrate subscriptions.
- **Priority**: P2
- **Points**: 3
- **Dependencies**: US-001
- **Status**: Delivered in Sprint 002
- **Acceptance Criteria**:
  - [ ] Given existing feeds and folders, When I run `rss:opml:export backup.xml`, Then a valid OPML file is created with all feeds organized by folder
  - [ ] Given no feeds, When I run the command, Then an OPML file with empty body is created
  - [ ] Given the exported file, When opened in another reader, Then all feeds and folders are recognized correctly

---

## Module: PWA Support

### US-022: [DELIVERED] Web app manifest and icons
- **As a** public visitor, **I want** to install the app on my device, **so that** I can access it like a native app.
- **Priority**: P1
- **Points**: 2
- **Dependencies**: US-015
- **Status**: Delivered in Sprint 003
- **Acceptance Criteria**:
  - [ ] Given the app is loaded, Then a valid web app manifest is served with name, icons, theme color, and display mode
  - [ ] Given the manifest, Then app icons for required sizes (192x192, 512x512) are available
  - [ ] Given a mobile browser, Then the browser shows an "install" prompt for the PWA

### US-023: [DELIVERED] Service worker for offline caching
- **As a** public visitor, **I want** the app to cache its shell for offline access, **so that** it loads quickly on return visits.
- **Priority**: P2
- **Points**: 3
- **Dependencies**: US-022
- **Status**: Delivered in Sprint 003
- **Acceptance Criteria**:
  - [ ] Given the app is loaded, Then a service worker is registered and caches the app shell (HTML, CSS, JS)
  - [ ] Given the app is cached, When I revisit offline, Then the app shell loads from cache
  - [ ] Given a new version is deployed, When online, Then the service worker updates the cache

---

## Module: Search

### US-024: [DELIVERED] Full-text search
- **As a** public visitor, **I want** to search across all articles by keyword, **so that** I can find specific content.
- **Priority**: P3
- **Points**: 5
- **Dependencies**: US-015
- **Status**: Delivered in Sprint 003
- **Acceptance Criteria**:
  - [ ] Given a search query, When I submit the search, Then articles matching the query in title or content are shown
  - [ ] Given search results, Then each result shows title, feed name, published date, and a highlighted excerpt
  - [ ] Given no matching articles, When searched, Then a "no results found" message is shown

### US-025: [DELIVERED] Search with date and folder filters
- **As a** public visitor, **I want** to combine search with date and folder filters, **so that** I can narrow down results.
- **Priority**: P3
- **Points**: 3
- **Dependencies**: US-024, US-016, US-017
- **Status**: Delivered in Sprint 003
- **Acceptance Criteria**:
  - [ ] Given a search within a date range, When results are shown, Then only articles within that range appear
  - [ ] Given a search within a folder, When results are shown, Then only articles from that folder appear

---

## Module: UI/UX Polish

### US-026: [DELIVERED] Show favicon before feed name
- **As a** public visitor, **I want** to see each feed's favicon next to its name in article cards and the reading modal, **so that** I can quickly identify the source visually.
- **Priority**: P1
- **Points**: 3
- **Dependencies**: US-018
- **Status**: Delivered in Sprint 004
- **Acceptance Criteria**:
  - [ ] Given an article card, When displayed, Then the feed's favicon appears before the feed name
  - [ ] Given an article in the reading modal, When displayed, Then the feed's favicon appears next to the feed name in the modal header
  - [ ] Given a feed with no favicon, When displayed, Then a fallback icon is shown instead
  - [ ] Given favicons are loaded, Then they are cached to avoid repeated external requests

### US-027: [DELIVERED] Improve article card hover effects
- **As a** public visitor, **I want** article cards to have a clear visual hover effect, **so that** I can tell which card I'm about to click.
- **Priority**: P1
- **Points**: 1
- **Dependencies**: US-018
- **Status**: Delivered in Sprint 004
- **Acceptance Criteria**:
  - [ ] Given an article card, When I hover over it, Then a visible shadow/elevation change and subtle border color change occurs
  - [ ] Given an article card, When I hover over it, Then the cursor changes to pointer
  - [ ] Given the transition, When hovering on/off, Then it animates smoothly (not instant)

### US-028: [DELIVERED] SPA-like navigation for dates, folders, and search
- **As a** public visitor, **I want** to switch between dates, filter by folder, and search without the page reloading, **so that** the experience feels fast and fluid like a native app.
- **Priority**: P1
- **Points**: 8
- **Dependencies**: US-016, US-017, US-024
- **Status**: Delivered in Sprint 004
- **Acceptance Criteria**:
  - [ ] Given I am on the homepage, When I click a date navigation link, Then the article list updates without a full page reload
  - [ ] Given I am on the homepage, When I click a folder filter pill, Then the article list updates without a full page reload
  - [ ] Given I am searching, When I submit the search form, Then results appear without a full page reload
  - [ ] Given I navigate via SPA, When done, Then the browser URL updates to reflect the current view
  - [ ] Given I navigate via SPA, When I press browser back/forward, Then the correct previous view is restored
  - [ ] Given a navigation is in progress, Then a subtle loading indicator is shown

---

## Module: Scheduled Fetching & Feed Health

### US-029: [DELIVERED] Schedule automatic feed fetching every 4 hours
- **As a** the site owner, **I want** feeds to be fetched automatically every 4 hours, **so that** new articles appear without manual intervention.
- **Priority**: P0
- **Points**: 2
- **Dependencies**: None
- **Status**: Delivered in Sprint 005
- **Acceptance Criteria**:
  - [ ] Given the scheduler is running, When 4 hours have passed, Then `rss:fetch` runs automatically
  - [ ] Given the scheduler is configured, Then it runs even when no one is actively using the site

### US-030: [DELIVERED] Skip articles without a publication date
- **As a** the site owner, **I want** articles that lack a publication date to be skipped during fetch, **so that** the database only contains properly dated articles.
- **Priority**: P1
- **Points**: 2
- **Dependencies**: None
- **Status**: Delivered in Sprint 005
- **Acceptance Criteria**:
  - [ ] Given an article in a feed with no published/pubDate/updated element, When fetched, Then the article is not saved to the database
  - [ ] Given an article with a valid publication date in the same feed, When fetched, Then that article is saved normally
  - [ ] Given an article is skipped, When the fetch completes, Then the summary output notes how many articles were skipped

### US-031: [DELIVERED] Track and auto-disable feeds with consecutive fetch errors
- **As a** the site owner, **I want** feeds that repeatedly fail to be tracked and eventually auto-disabled, **so that** broken feeds don't waste resources indefinitely.
- **Priority**: P1
- **Points**: 8
- **Dependencies**: US-029
- **Status**: Delivered in Sprint 005
- **Acceptance Criteria**:
  - [ ] Given a feed fetch succeeds, When complete, Then the feed's error_count is reset to 0
  - [ ] Given a feed fetch fails, When complete, Then the feed's error_count is incremented by 1
  - [ ] Given a feed's error_count reaches 8, When the fetch fails, Then the feed is automatically disabled (is_enabled = false)
  - [ ] Given a disabled feed, When the scheduler runs, Then that feed is skipped entirely
  - [ ] Given a feed that was previously failing, When a fetch succeeds, Then error_count is cleared even if it was > 0

### US-032: [DELIVERED] CLI command to list and re-enable disabled feeds
- **As a** the site owner, **I want** to see which feeds are disabled and be able to re-enable them, **so that** I can manage feed health from the CLI.
- **Priority**: P2
- **Points**: 2
- **Dependencies**: US-031
- **Status**: Delivered in Sprint 005
- **Acceptance Criteria**:
  - [ ] Given I run `rss:feed:health`, When there are disabled feeds, Then they are listed with name, URL, error_count, and last error message
  - [ ] Given I run `rss:feed:enable {feed}`, Then the feed is re-enabled (is_enabled = true, error_count = 0)
  - [ ] Given I run `rss:feed:health`, When all feeds are healthy, Then a "All feeds healthy" message is shown

---

## Module: Read State & Image Styling

### US-033: [DELIVERED] Mark articles as read with localStorage
- **As a** public visitor, **I want** articles I've already read to be visually distinct from unread ones, **so that** I can easily see what's new.
- **Priority**: P1
- **Points**: 3
- **Dependencies**: US-018
- **Status**: Delivered in Sprint 006
- **Acceptance Criteria**:
  - [ ] Given I open an article in the modal, When it loads, Then the article ID is stored in localStorage
  - [ ] Given an article is marked as read, When the article list renders, Then the card appears dimmed/muted compared to unread articles
  - [ ] Given localStorage entries exist, When they are older than 7 days, Then they are automatically cleaned up
  - [ ] Given a read article, When I view it, Then the title/text color is lighter and the card has reduced visual weight

### US-034: [DELIVERED] Round images in article modal
- **As a** public visitor, **I want** images in the article reading modal to have rounded corners, **so that** they look polished.
- **Priority**: P2
- **Points**: 2
- **Dependencies**: US-018
- **Status**: Delivered in Sprint 006
- **Acceptance Criteria**:
  - [ ] Given an article with images in its content, When displayed in the modal, Then all images have border-radius applied
  - [ ] Given images in the modal, When rendered, Then the rounding is consistent and not overly dramatic

---

## Module: Smart Recent Feeds

### US-035: [DELIVERED] Smart homepage — Recent Feeds fallback when today has few articles
- **As a** public visitor, **I want** the homepage to always show a meaningful list of articles, **so that** the page doesn't feel empty on days with few or no new posts.
- **Priority**: P1
- **Points**: 5
- **Dependencies**: US-015
- **Status**: Delivered in Sprint 007
- **Acceptance Criteria**:
  - [ ] Given today has 20 or more articles, When I visit the homepage, Then the page title shows "Today's Feeds" and only today's articles are displayed (existing behavior unchanged)
  - [ ] Given today has fewer than 20 articles, When I visit the homepage, Then the page title shows "Recent Feeds" and the 20 most recent articles are displayed (regardless of date)
  - [ ] Given "Recent Feeds" mode is active, When a folder filter is applied, Then only articles from feeds in that folder are shown (up to 20, still ordered by recency)
  - [ ] Given I navigate to a specific past date via date navigation, When the page loads, Then the current date-scoped behavior applies (no "Recent Feeds" fallback, only that date's articles)
  - [ ] Given "Recent Feeds" mode, When the article count is displayed, Then it reflects the actual number of articles shown
  - [ ] Given the SPA fetches the homepage fragment, When today has < 20 articles, Then the fragment returns "Recent Feeds" mode with 20 recent articles

### US-036: [DELIVERED] Show date and time on article cards in Recent Feeds mode
- **As a** public visitor, **I want** article cards to show both date and time when viewing "Recent Feeds", **so that** I can tell which articles are from today versus previous days.
- **Priority**: P1
- **Points**: 2
- **Dependencies**: US-035
- **Status**: Delivered in Sprint 007
- **Acceptance Criteria**:
  - [ ] Given "Today's Feeds" mode is active, When article cards are rendered, Then only the time is shown (existing behavior — e.g., "3:45 PM")
  - [ ] Given "Recent Feeds" mode is active, When article cards are rendered, Then both date and time are shown (e.g., "May 4, 3:45 PM")
  - [ ] Given "Recent Feeds" mode, When an article is from today, Then today's date is still shown alongside the time for consistency
  - [ ] Given a past date is navigated to (not today), When article cards are rendered, Then only the time is shown (existing behavior unchanged)

---

## Module: Search Results Date Display

### US-037: [DELIVERED] Show date+time on article cards in search results
- **As a** public visitor, **I want** article cards in search results to show both date and time, **so that** I can tell which articles are recent when results span multiple dates.
- **Priority**: P2
- **Points**: 2
- **Dependencies**: US-024, US-036
- **Status**: Delivered in Sprint 008
- **Acceptance Criteria**:
  - [ ] Given search results spanning multiple dates, When article cards are rendered, Then both date and time are shown (e.g., "May 4, 3:45 PM")
  - [ ] Given search results from a single date, When article cards are rendered, Then both date and time are still shown for consistency
  - [ ] Given search results, When the SPA fetches a fragment, Then date+time is shown in the fragment as well
