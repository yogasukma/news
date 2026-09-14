# Product Requirements Document: RSS Reader

## 1. Overview

- **Product name**: RSS Reader
- **Summary**: A minimal, self-hosted RSS reader with a public-facing read-only web interface and CLI-based administration. Anyone can browse and read articles in a clean distraction-free interface. Feed management — subscribing, organizing, importing/exporting — is handled entirely through Laravel Artisan CLI commands. The app is a Progressive Web App (PWA), installable on any device for a native-like reading experience.
- **Problem statement**: Existing RSS readers are either bloated with features, require subscriptions, or don't offer the clean reading experience desired. A self-hosted solution gives full control over data, a tailored minimal reading experience, and a public aggregator for sharing curated feeds.

## 2. Target Users

- **Owner (admin)**: Manages feeds and folders via Artisan CLI commands
- **Public visitors**: Browse and read articles — no login, no management capabilities
- **Use cases**:
  - Owner subscribes to blogs, news sites, and YouTube channels via CLI
  - Public visitors discover and read curated articles in a clean interface
  - Owner organizes feeds into folders via CLI
  - Owner imports existing subscriptions from other readers via OPML CLI command
  - Anyone can access the reader from any device via PWA install

## 3. Goals & Success Metrics

- **Goals**:
  - Have a working RSS aggregator with a public read-only interface
  - Clean, fast reading experience with no distractions
  - Reliable feed fetching and article storage
  - Simple CLI-based feed management for the owner
  - Accessible from mobile and desktop via PWA
  - A clean, duplication-free article stream and a healthy feed ecosystem (broken feeds recover automatically)
- **Success metrics**:
  - Successfully subscribes to and fetches articles from RSS/Atom feeds
  - Article content renders cleanly and readably for public visitors
  - OPML import/export works with standard formats
  - PWA installs and works on both desktop and mobile browsers
  - CLI commands provide clear output and error handling
  - Zero duplicate articles in the stream, verified by URL/permalink regardless of source feed
  - `rss:feed:add` accepts both direct feed URLs and website URLs (auto-discovers the site's real feed via HTML link tags)
  - Disabled feeds automatically recover after one month of inactivity without manual intervention
  - Visitors can see all sources and their last fetch times

## 4. Feature Modules

### Module 1: Feed Management (CLI)
- **Description**: Manage RSS/Atom feed subscriptions entirely through Artisan CLI commands
- **Key features**:
  - `rss:feed:add {url}` — Subscribe to a feed by URL (auto-detect RSS/Atom)
  - **Feed URL auto-discovery** — `rss:feed:add` also accepts website URLs (e.g., `https://example.com`). When the URL returns an HTML page instead of a feed, scan the page source for `<link rel="alternate">` feed tags (`application/rss+xml` and `application/atom+xml`), prefer RSS over Atom when both exist, resolve relative `href`s against the website URL, and subscribe to the discovered feed (stored in `feed.url`) with the original website URL stored as `site_url`. If no feed link tag is found, fail with a clear error message and create nothing.
  - `rss:feed:remove {feed}` — Unsubscribe from a feed
  - `rss:feed:list` — List all subscribed feeds with details
  - `rss:feed:info {feed}` — Show feed details (title, URL, article count, last fetched)
  - `rss:folder:create {name}` — Create a folder
  - `rss:folder:delete {folder}` — Delete a folder (optionally reassign feeds)
  - `rss:folder:move {feed} {folder}` — Move a feed into a folder
  - `rss:folder:list` — List all folders with their feeds
  - Favicon fetching for feeds (automatic on add)
- **Priority**: Must-have

### Module 2: Feed Fetching
- **Description**: Fetch and parse RSS/Atom feeds to discover new articles
- **Key features**:
  - Parse RSS 2.0 and Atom feed formats
  - Store articles with title, URL, content, author, published date, and cover image
  - **Global duplicate prevention** — before saving, check the article's URL/permalink against ALL stored articles; skip (or update) instead of creating a duplicate
  - `rss:fetch` — Fetch all feeds (run via scheduler, e.g., every hour)
  - `rss:fetch {feed}` — Fetch a single feed
  - Handle feed errors gracefully (invalid XML, timeouts, dead feeds)
  - Log fetch results and errors
- **Priority**: Must-have

### Module 3: Article Reading (Public Web UI)
- **Description**: A daily-digest style reader — one page per date, single column layout, like a personal newspaper
- **Key features**:
  - **Single column layout** — clean, focused, typography-first design
  - **Default view: "Today's Feeds"** — shows all articles published today
  - **Date navigation** — browse to yesterday, a specific date, or previous/next day; one page = one date, no pagination
  - **Category/folder filter** — filter articles by a specific folder/group or show all; filter works within the selected date
  - **Article cards** — each article shows title, source feed name, published time, and excerpt; click to expand or open full article
  - Open original article in popup modal window
  - Feed/folder list for filtering (not full sidebar navigation — minimal controls)
  - **No login required** — fully public read-only access
  - **No read/unread tracking** — no per-user state
  - **No starring/favorites** — no per-user state
  - **No pagination** — all articles for the selected date are shown
- **Priority**: Must-have

### Module 4: OPML Import/Export (CLI)
- **Description**: Import and export feed subscriptions using the OPML standard format via CLI
- **Key features**:
  - `rss:opml:import {file}` — Import OPML file (parse feeds and folders)
  - `rss:opml:export {file}` — Export subscriptions as OPML file
  - Handle duplicate feeds on import gracefully
  - Report import summary (added, skipped, errors)
- **Priority**: Must-have

### Module 5: PWA Support
- **Description**: Make the app installable as a Progressive Web App
- **Key features**:
  - Web app manifest (name, icons, theme color, display mode)
  - Service worker for basic offline caching of the app shell
  - App icons for various sizes
  - Install prompt handling
- **Priority**: Must-have

### Module 6: Search
- **Description**: Search across all articles by keyword
- **Key features**:
  - Full-text search across article titles and content
  - Display search results with relevance
  - Article cards show date+time (since results span multiple dates)
- **Priority**: Nice-to-have

### Module 7: UI/UX Polish
- **Description**: Enhance the reading experience with favicons, better interactions, and SPA-like navigation
- **Key features**:
  - Show favicon before each feed/site name in article cards and modal
  - More visible hover effects on article cards
  - SPA-like navigation — switch dates, folders, and search without full page reloads
  - Smooth transitions during navigation
  - Browser back/forward support via History API
- **Priority**: Must-have

### Module 8: Scheduled Fetching & Feed Health
- **Description**: Automate feed fetching on a schedule, skip invalid articles, and auto-disable chronically broken feeds
- **Key features**:
  - Scheduled fetch every 4 hours via Laravel scheduler
  - Skip articles without a publication date (do not save them)
  - Track consecutive fetch errors per feed with an error counter
  - Auto-disable feeds after 8 consecutive errors
  - Clear error count on successful fetch
  - CLI command to list/reenable disabled feeds
  - **Automatic recovery** — a dedicated scheduled command re-enables feeds that have been disabled for over a month (resets error counter)
- **Priority**: Must-have

### Module 9: Read State & Image Styling
- **Description**: Track which articles the visitor has read using browser localStorage, with visual distinction and automatic cleanup. Also round images in the article modal.
- **Key features**:
  - Mark articles as read when opened in the modal (stored in localStorage)
  - Visual feedback: read articles appear dimmed/muted in the article list
  - localStorage entries expire after 7 days (auto-cleaned)
  - All images in the article modal have border-radius
- **Priority**: Must-have

### Module 10: Smart Recent Feeds
- **Description**: Enhance the homepage to handle days with few or no articles by automatically switching to a "Recent Feeds" mode that backfills from previous days.
- **Key features**:
  - **Threshold behavior**: When visiting the homepage (today), if today's articles total less than 20, the view switches from "Today's Feeds" to "Recent Feeds"
  - **Recent Feeds mode**: Shows the 20 most recent articles across all dates (e.g., backfilling from yesterday, the day before, etc.)
  - **Date+time display**: In "Recent Feeds" mode, article cards show both the date and time (not just time), since articles span multiple days
  - **Only applies to homepage**: When navigating to a specific past date, the current date-scoped behavior remains unchanged
  - **Folder filter works**: The folder filter still applies within the "Recent Feeds" result set
  - **Date navigation available**: Date picker and prev/next links are visible in "Recent Feeds" mode; navigating to a date switches to that date's articles
- **Priority**: Must-have

### Module 11: Fetch Deduplication
- **Description**: Guarantee that no article is stored twice. The article's URL/permalink is the source of truth for uniqueness, checked globally across ALL feeds (not just within a single feed).
- **Key features**:
  - On fetch, resolve the article's permalink (URL with tracking parameters normalized where feasible)
  - Check if the URL already exists anywhere in the articles table
  - If it exists: update mutable fields (title, content, author, cover image) on the existing record instead of creating a duplicate
  - If it does not exist: create the article as usual
  - External IDs (e.g., `<guid>`) continue to be stored, but URL is the primary dedup key
- **Priority**: Must-have

### Module 12: Feed Health Recovery
- **Description**: Disabled feeds (8+ consecutive errors) recover automatically after one month of inactivity, removing the need for manual re-enabling.
- **Key features**:
  - Dedicated artisan command (e.g., `rss:feed:recover`) that scans feeds with `is_enabled = false`
  - A feed qualifies for recovery when its `updated_at` is older than 1 month (inactive for 30+ days)
  - On recovery: `error_count` is reset to 0 and the feed is re-enabled (`is_enabled = true`) so the next scheduled fetch includes it
  - Must report which feeds were recovered (and how many) in the CLI output
  - Registered in the Laravel scheduler to run daily
- **Priority**: Must-have

### Module 13: Sources Directory
- **Description**: A public page listing ALL subscribed sources (regardless of date) with their last fetch time, styled like a table of contents for quick scanning.
- **Key features**:
  - New route/page (e.g., `/sources`) accessible from a "Sources" link placed after the date picker (separated by a divider) on the article page
  - Page title: "RSS Sources", with a "Back to feeds" link at the top returning to the article page (SPA-compatible)
  - Lists every feed in the system — not filtered by the currently selected date
  - Each row shows: favicon + feed name on the left, last fetched time on the right (TOC-style: name left-aligned, time right-aligned)
  - Each source row links to the **source detail page** (`/sources/{feed}`) via internal navigation (SPA-compatible); sources without a favicon still link to their detail page
  - Sorted by most recently fetched first (descending `last_fetched_at`)
  - Feeds with no fetch history appear at the bottom
  - SPA-compatible: works with fragment navigation like the other pages
- **Priority**: Must-have

### Module 14: Modal Interaction & Content Polish
- **Description**: Improve the article reading modal so it feels native: easy to dismiss, safe browsing, and consistent image rendering.
- **Key features**:
  - Click anywhere OUTSIDE the modal content (the dark backdrop area of the full screen) to close the modal — including the scrollable area around the content
  - All links inside the modal content body open in a new tab (`target="_blank"` with `rel="noopener noreferrer"`)
  - Images inside the modal content always render at `width: 100%`, `max-width: 100%`, `height: auto` (responsive, never overflow)
- **Priority**: Must-have

### Module 15: Source Detail Page
- **Description**: A per-source page showing all articles published by a single feed, addressed by the feed's numeric id (`/sources/{id}`). Replaces the previous "open source site in new tab" behavior from the sources directory.
- **Key features**:
  - New route `GET /sources/{feed}` (route-model binding on feed id); non-existent ids return 404
  - Sources list rows now navigate to the detail page (internal, SPA-compatible) instead of opening the external site in a new tab
  - Header follows the article page design: favicon + site title, plus a clickable URL of the source site (opens the external site in a new tab with `rel="noopener noreferrer"`) with a small external-link icon
  - Below the header: the list of articles for that source, newest first
  - Article list is **paginated** (matching the search page pattern, e.g., 30 per page) since a single source can have many articles
  - Article cards show date+time (articles span multiple days) and clicking an article opens it in the existing modal
  - SPA-compatible: supports `?fragment=1` content swapping like all other pages
  - Sources without a valid site URL show the favicon + title header without the clickable external link
  - **Source names link to their detail page everywhere**: article cards (homepage, date pages, search results, source detail list) and the article modal header link to `/sources/{feed}`
- **Priority**: Must-have

## 5. Non-Functional Requirements

- **Performance**: Article list should load in under 500ms; feed fetching should not block the web UI
- **Security**: No authentication for public reading; CLI-only admin access; input sanitization on feed URLs; rate limiting on public routes
- **Scalability**: Designed for one curated collection — hundreds of feeds, tens of thousands of articles
- **Accessibility**: Semantic HTML, keyboard navigable, proper ARIA labels, sufficient color contrast
- **Responsiveness**: Fully responsive — optimized for mobile reading

## 6. Constraints & Assumptions

- **Technology**: Laravel 12, PHP 8.2, SQLite, Tailwind CSS v4, Blade templates
- **Architecture**: Public read-only web UI + CLI-based administration (no admin web panel)
- **Self-hosted**: Deployed on a personal server
- **Storage**: Articles stored in the database (no external media storage needed for article content)
- **Feed fetching**: Server-side via Laravel scheduled commands (no client-side fetching)
- **No user state**: No per-visitor tracking, cookies, or accounts

## 7. Out of Scope

- Admin web panel or dashboard
- User registration, authentication, or accounts
- Per-user read/unread state or bookmarks
- Social features (sharing, comments)
- Article recommendations or AI summaries
- Push notifications
- Read-later integrations (Pocket, Instapaper)
- Browser extension for subscribing
- Full-text article extraction from partial RSS feeds

## 8. References

- OPML specification: http://opml.org/spec2.opml
- RSS 2.0 specification: https://www.rssboard.org/rss-specification
- Atom specification: https://tools.ietf.org/html/rfc4287
- PWA documentation: https://web.dev/progressive-web-apps/
- Similar products for design inspiration: Feedbin, Miniflux, NetNewsWire
