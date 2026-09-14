# Technical Design: Sprint 012 — Feed Discovery

## 1. Architecture Overview

Pure addition to the CLI layer. A new `FeedDiscovery` service resolves a real RSS/Atom feed URL from an HTML website page; `FeedAddCommand` uses it as a fallback when direct feed parsing fails. **No schema changes, no new dependencies.**

```
rss:feed:add {url}
  │
  ├─ valid http(s) URL? ─────────── no → "Invalid URL" error (unchanged)
  │
  ├─ FeedParser::parse($url) ────── ok   → $feedUrl = $url            (existing direct-feed path)
  │        │
  │        └─ throws (HTML/not-XML)
  │
  ├─ FeedDiscovery::discover($url) ── no feed link → error "No RSS/Atom feed link found"
  │        │
  │        └─ feed URL found → FeedParser::parse($feedUrl)  (existing validation is the gate)
  │
  ├─ duplicate check on $feedUrl (RESOLVED url) ── exists → "Already subscribed" error
  │
  └─ Feed::create(url = $feedUrl, site_url = $url | parsed site_url, ...)
         + "Discovered feed 'Title' at <url>" output when discovery was used
```

Flow rule: **direct feed URLs are completely unchanged** (parse succeeds on first try, discovery never runs, `site_url` comes from the parser as today). Discovery only engages when the response isn't a feed.

## 2. Technology Stack

No new dependencies. Existing stack reused as-is: Laravel 12, PHP 8.2, SQLite, Pest 3. HTML parsing uses PHP's built-in **`DOMDocument` + XPath** (ext-dom confirmed installed — line 4: `dom`) with `libxml_use_internal_errors()`, mirroring how `FeedParser::parseXml()` already handles libxml errors. Network access uses the identical `Http::timeout(30)->withUserAgent('RSSReader/1.0')` pattern as `FeedParser::parse()` so existing `Http::fake()` test conventions apply.

## 3. Data Model

No schema changes. Reads/writes existing `feeds` columns only:
- `feeds.url` — **resolved feed URL** (discovered URL when discovery ran; unchanged entered URL for direct feeds) — this is what future `rss:fetch` calls hit
- `feeds.site_url` — the **original website URL** the owner typed (when discovery ran); parser-derived site URL otherwise
- `feeds.title`, `feeds.description` — parsed from the (discovered) feed as today

## 4. File Structure

```
app/Services/FeedDiscovery.php                  ← NEW (discover() + resolveUrl())
app/Console/Commands/FeedAddCommand.php         ← MODIFY (discovery fallback, resolved-URL dedupe, output)
tests/Unit/FeedDiscoveryTest.php                ← NEW (US-049)
tests/Feature/FeedAddCommandTest.php            ← MODIFY (US-050; duplicate test updated for new fetch-before-dedupe flow)
```

Naming conventions: `FeedDiscovery` follows the existing `FeedParser` service naming; methods return plain values and throw `\RuntimeException` with descriptive messages (same style `FeedParser` uses).

## 5. API Design

Not applicable — CLI-only change. (No web route changes; the public UI is untouched.)

## 6. Design Decisions

### 6.1 Discovery only as a parse-failure fallback
`FeedAddCommand` keeps calling `$parser->parse($url)` first — the existing `FeedParser` validation ("Failed to parse feed XML", "Unknown feed format") remains the authoritative gate exactly as the user requested. Discovery runs in the `catch` block. Cost: an HTML site costs one extra HTTP GET before discovery + one for the feed; acceptable for an admin-only CLI command (not a hot path). Direct feed URLs pay zero overhead and keep identical behavior.

### 6.2 HTML parsing with DOMDocument + XPath (not regex)
`//link[@rel="alternate"]` — hmm, `rel` is a space-separated list by spec, so the XPath uses:
`//link[contains(concat(' ', normalize-space(@rel), ' '), ' alternate ')]`
Filter candidates by `@type`:
- RSS candidates: `application/rss+xml`
- Atom candidates: `application/atom+xml`
**Prefer RSS over Atom** (user decision): walk candidates in document order, return the first RSS hit; fall back to the first Atom hit. DOMDocument handles attribute order, malformed HTML, and entity noise that regex would choke on. `LIBXML_NONET` prevents network access during parsing.

### 6.3 URL resolution
`resolveUrl(string $baseUrl, string $href): string` handles the four real-world cases:
| href form | example | resolution |
|---|---|---|
| absolute | `https://x.com/rss` | as-is |
| protocol-relative | `//x.com/rss` | prepend base scheme |
| root-relative | `/rss.xml` | `scheme://host` + path |
| path-relative | `feed.xml` | resolve against base path (RFC 3986 merge) |

Result is validated with `filter_var(FILTER_VALIDATE_URL)` + `http(s)` scheme check (same guard the command already uses) — a malicious/typo'd href can never produce a non-http URL.

### 6.4 Resolved-URL duplicate check (moved after resolution)
The dedupe can only be correct against the **resolved** feed URL (US-050 AC1), which isn't known until after parse/discovery. So the `Feed::where('url', ...)->exists()` check moves after resolution. Direct feeds keep identical semantics (same URL, just checked a fetch later). Existing test `rejects a duplicate feed URL` must fake an HTTP response now — updated as part of US-050.

### 6.5 site_url semantics
When discovery runs, `site_url` = the URL the owner typed (user decision). When a direct feed is added, `site_url` comes from the parser (`channel.link` / Atom alternate) exactly as today — zero behavior change.

### 6.6 Combined error for the "not a feed" case
If the entered URL is neither a feed nor a discoverable HTML page, the command reports both signals in one message:
`Failed to fetch or parse feed: <parse error> No RSS/Atom feed link found on the page.`
This preserves the AC5 test contract (`Failed to fetch or parse` prefix) while adding the discovery signal.

### 6.7 Output confirms discovery
On the discovery path, before subscribing: `Discovered feed '<title>' at <url>` (US-050 AC4) — the owner always sees which URL was actually stored.

## 7. Security Considerations

- **SSRF trust model**: unchanged from existing `rss:feed:add` — the CLI is admin-only; the owner already can point the command at any URL. Discovery follows URLs found in HTML, so a site could advertise a feed URL pointing at an internal address; the command fetches it — same trust assumption as the existing direct-URL fetch. Noted as accepted risk (CLI, owner-run).
- **URL validation**: `resolveUrl()` output must pass `filter_var` + `http(s)` check before being returned; the discovery href can never produce ftp://, file://, javascript://, etc.
- **HTML parsing hardening**: `libxml_use_internal_errors(true)` + `LIBXML_NONET` (no external entity/network loading); parse errors suppressed, never surfaced raw.
- **Injection**: feed URL stored in `feeds.url` is used only in HTTP client requests and display — no SQL/HTML context. Parser sanitization (`sanitizeHtml`) already handles feed content; untouched.
- **No hardcoded secrets**, no logging of response bodies — only URLs and status codes in exception messages.

## 8. Risks & Mitigations

- **R1: Site serves a feed but with an unusual content-type or charset** → Mitigation: discovery is content-type-agnostic (DOMDocument parses whatever bytes come back; charset handled implicitly). Direct parse failure → discovery → parse(discovered) chain still works.
- **R2: Site with trailing-slash/redirect feed URLs** → Mitigation: `Http` client follows redirects (default); resolved relative hrefs are origin-based, so `/feed` on `https://example.com` resolves correctly.
- **R3: Duplicate-check test change** (`rejects a duplicate feed URL`) → Mitigation: update the existing test to fake a valid feed response; new flow fetches before dedupe. Explicitly listed in US-050 task breakdown.
- **R4: Discovery of a page that is actually XML (unknown feed format)** → Mitigation: DOMDocument finds no `alternate` link tags → discovery throws "No RSS/Atom feed link found"; combined error message still surfaces the original "Unknown feed format" signal.