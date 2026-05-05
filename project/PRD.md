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
- **Success metrics**:
  - Successfully subscribes to and fetches articles from RSS/Atom feeds
  - Article content renders cleanly and readably for public visitors
  - OPML import/export works with standard formats
  - PWA installs and works on both desktop and mobile browsers
  - CLI commands provide clear output and error handling

## 4. Feature Modules

### Module 1: Feed Management (CLI)
- **Description**: Manage RSS/Atom feed subscriptions entirely through Artisan CLI commands
- **Key features**:
  - `rss:feed:add {url}` — Subscribe to a feed by URL (auto-detect RSS/Atom)
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
