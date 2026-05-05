# Test Report: Sprint 007

## Summary
- Total tests: 200 (all suites)
- Passed: 200
- Failed: 0
- Sprint-specific tests: 12 new (all passing)

## Results by User Story

### US-035: Smart homepage — Recent Feeds fallback when today has few articles
| Test | Description | Result |
|------|-------------|--------|
| shows Todays Feeds when 20 or more articles today | ≥20 articles → "Today's Feeds" mode | PASS |
| shows Recent Feeds when fewer than 20 articles today | <20 articles → "Recent Feeds" mode | PASS |
| shows Recent Feeds when no articles today but articles exist on previous days | 0 today, articles on past days → recent mode | PASS |
| backfills recent articles from previous days up to 20 | 3 today + 17 yesterday → 20 articles shown | PASS |
| does not trigger recent mode on past dates even with fewer than 20 articles | Past date → date mode, no fallback | PASS |
| hides date navigation in recent mode | No `data-spa-date` in recent mode | PASS |
| shows date navigation in today mode | `data-spa-date` present with ≥20 articles | PASS |
| applies folder filter in recent mode | Folder filter scopes recent articles correctly | PASS |
| returns recent mode in SPA fragment | `?fragment=1` returns correct mode | PASS |

### US-036: Show date+time on article cards in Recent Feeds mode
| Test | Description | Result |
|------|-------------|--------|
| shows time-only on article cards in today mode | Today mode → `g:i A` format | PASS |
| shows date and time on article cards in recent mode | Recent mode → `M j, g:i A` format | PASS |
| shows time-only on article cards for past date pages | Past date nav → `g:i A` format (unchanged) | PASS |

## Acceptance Criteria Coverage

| Story | Criterion | Test | Status |
|-------|-----------|------|--------|
| US-035 | AC1: ≥20 today → "Today's Feeds" unchanged | shows Todays Feeds when 20 or more | PASS |
| US-035 | AC2: <20 today → "Recent Feeds" with 20 most recent | shows Recent Feeds when fewer than 20 | PASS |
| US-035 | AC3: Folder filter works in recent mode | applies folder filter in recent mode | PASS |
| US-035 | AC4: Past dates keep date-scoped behavior | does not trigger recent mode on past dates | PASS |
| US-035 | AC5: Article count reflects actual number shown | backfills up to 20 + shows count | PASS |
| US-035 | AC6: SPA fragment returns correct mode | returns recent mode in SPA fragment | PASS |
| US-036 | AC1: Today mode → time-only | shows time-only on article cards in today mode | PASS |
| US-036 | AC2: Recent mode → date+time | shows date and time on article cards in recent mode | PASS |
| US-036 | AC3: Today's articles in recent mode still show date | covered by AC2 (all articles in recent mode show date) | PASS |
| US-036 | AC4: Past date nav → time-only | shows time-only on article cards for past date pages | PASS |
