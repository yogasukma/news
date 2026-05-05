# RSS Reader

A minimal, self-hosted RSS reader with a public-facing read-only web interface and CLI-based administration. Browse and read articles in a clean, distraction-free interface — no accounts, no login required.

## Features

- **Public reading interface** — single-column, typography-first design. One page per date, like a personal newspaper
- **SPA-like navigation** — switch dates, filter by folder, and search without page reloads
- **Full-text search** — search across all articles by keyword, combinable with date and folder filters
- **Read state tracking** — articles you've opened are visually dimmed (stored in localStorage, auto-cleaned after 7 days)
- **PWA installable** — add to home screen on any device for a native-like reading experience with offline caching
- **CLI administration** — manage feeds, folders, and imports entirely through Artisan commands
- **Feed health monitoring** — broken feeds are tracked and auto-disabled after 8 consecutive errors
- **OPML import/export** — migrate subscriptions to/from any RSS reader

## Tech Stack

- **Backend**: Laravel 12, PHP 8.2
- **Database**: SQLite
- **Frontend**: Blade templates, Tailwind CSS v4, vanilla JavaScript
- **Testing**: Pest 3

## Quick Start

```bash
# Clone the repository
git clone <repo-url> rss && cd rss

# Install dependencies
composer install && npm install

# Set up environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Start the development server
composer run dev
```

## CLI Commands

### Feed Management

```bash
php artisan rss:feed:add {url}          # Subscribe to a feed by URL (auto-detects RSS/Atom)
php artisan rss:feed:remove {feed}      # Unsubscribe from a feed
php artisan rss:feed:list               # List all subscribed feeds
php artisan rss:feed:info {feed}        # Show feed details
php artisan rss:feed:health             # Show disabled/unhealthy feeds
php artisan rss:feed:enable {feed}      # Re-enable a disabled feed
```

### Folder Management

```bash
php artisan rss:folder:create {name}    # Create a folder
php artisan rss:folder:delete {folder}  # Delete a folder (feeds moved to uncategorized)
php artisan rss:folder:list             # List all folders with feed counts
php artisan rss:folder:move {feed} {folder}  # Move a feed into a folder
```

### Fetching

```bash
php artisan rss:fetch                   # Fetch all enabled feeds
php artisan rss:fetch {feed}            # Fetch a single feed
```

Scheduled automatically every 4 hours via the Laravel scheduler.

### OPML Import/Export

```bash
php artisan rss:opml:import {file}      # Import feeds from an OPML file
php artisan rss:opml:export {file}      # Export subscriptions as OPML
```

## Project Structure

```
app/
├── Console/Commands/   # 13 Artisan commands (rss:feed:*, rss:folder:*, rss:fetch, rss:opml:*)
├── Http/Controllers/   # Web controllers for the reading interface
├── Models/             # Feed, Article, Folder (Eloquent models)
├── Services/           # FeedParser, OpmlParser
resources/
├── views/              # Blade templates (articles, components)
├── js/                 # SPA navigation, read state, PWA service worker
public/                 # PWA manifest and icons
database/
├── migrations/         # Folders, feeds, articles tables + health columns
```

## Testing

```bash
php artisan test --compact
```

## License

MIT
