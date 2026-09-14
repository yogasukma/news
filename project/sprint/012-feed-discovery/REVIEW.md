# Code Review: Sprint 012

## Summary
- Files reviewed: 5 (`FeedDiscovery.php`, `FeedAddCommand.php`, `FeedDiscoveryTest.php`, `FeedAddCommandTest.php`, `AcceptanceCriteriaTest.php`)
- Issues found: 6 (Critical: 0, Warning: 1, Info: 5)
- Issues fixed: 1

## Review Results

### File: app/Services/FeedDiscovery.php
- **[OK]** `resolveUrl()` rejects non-http(s) schemes (`javascript:`, `ftp:`, `file:`, `data:`, `mailto:`) before relative resolution, and re-validates every resolved URL with `filter_var` — no scheme escape possible.
- **[OK]** XXE/entity safety: `libxml_use_internal_errors(true)` + `LIBXML_NONET`; `loadHTML` does not load external entities, and libxml's default entity-expansion limits apply (no `LIBXML_PARSEHUGE`).
- **[OK]** `rel` matching uses `contains(concat(' ', normalize-space(@rel), ' '), ' alternate ')` — correct for space-separated rel lists; `type` is lowercased for case-insensitive comparison.
- **[Info]** SSRF exposure (documented risk in DESIGN.md §7): a site's HTML can advertise a feed URL on any http(s) host (e.g., internal addresses). Accepted — admin-only CLI, same trust model as the existing direct-URL fetch. The advertised URL is displayed in command output.
- **[Info]** IDN/punycode hrefs: `filter_var` rejects non-ASCII hosts, so a feed link on an IDN host is skipped → possible false "no feed found". Rare; punycode-form URLs still work.
- **[Info]** No response size cap: a very large page is fully buffered + parsed into DOM. Acceptable for an admin CLI; noted for future hardening.

### File: app/Console/Commands/FeedAddCommand.php
- **[Warning → Fixed]** Dedupe regression: moving the duplicate check after fetch meant re-adding an existing (but currently down) feed reported "Failed to fetch or parse" instead of "Already subscribed". Re-added the early duplicate check on the entered URL (before fetch) — restores old semantics at zero cost; the post-resolution check still catches duplicates that only differ after discovery (US-050 AC1).
- **[OK]** Discovery runs only in the `catch` of direct parse — direct feed URLs are byte-for-byte unchanged in behavior.
- **[OK]** Combined error message preserves the `Failed to fetch or parse feed:` contract (AC5 test) while surfacing the discovery signal.
- **[Info]** Up to 3 sequential HTTP requests on the discovery path (parse attempt + page + feed). Acceptable for a CLI command; no responsiveness impact.
- **[Info]** Pre-existing quirk (not introduced here): `Str($url)->startsWith` is case-sensitive, so `HTTPS://...` is rejected while `filter_var` accepts it. Out of scope.

### File: tests/Feature/FeedDiscoveryTest.php
- **[OK]** Covers: RSS-absolute, Atom-relative, RSS>Atom preference, root-relative, protocol-relative, path-relative, space-separated rel, uppercase type, no-link error, 503 error, non-http scheme rejection (10→11 tests after review addition).
- **[Info]** Could add IDN and query-string href cases; judged non-essential.

### File: tests/Feature/FeedAddCommandTest.php
- **[OK]** Covers: discovered-feed success (url/site_url assertions + "Discovered feed" output), duplicate-on-resolved-URL, discovered-feed parse failure, no-link error; direct-feed tests unchanged and passing.

### File: tests/Feature/AcceptanceCriteriaTest.php
- **[OK]** AC4 updated for post-fetch dedupe flow (fakes a valid feed response); AC1/AC2/AC3/AC5 contracts preserved.

## Overall Assessment
**Pass.** No critical or security-blocking issues. One warning (dedupe regression) fixed during review; remaining items are documented info-level notes with accepted risk in DESIGN.md. All 269 applicable tests pass (3 failures in `UiUxPolishTest`/`SourceDetailPageTest` are pre-existing on `dev`, unrelated to this sprint).