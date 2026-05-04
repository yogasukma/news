<?php

/**
 * Acceptance Criteria Tests for Sprint 001
 * Maps every AC from BACKLOG.md to a specific test.
 */

use App\Models\Article;
use App\Models\Feed;
use App\Models\Folder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schedule;

// ============================================================================
// US-005: Create a folder
// ============================================================================

describe('US-005: Create a folder', function () {
    it('AC1: creates folder with unique name and shows success message', function () {
        $this->artisan('rss:folder:create', ['name' => 'Tech'])
            ->assertSuccessful()
            ->expectsOutputToContain("Folder 'Tech' created");

        $folder = Folder::first();
        expect($folder->name)->toBe('Tech');
        expect($folder->slug)->toBe('tech');
    });

    it('AC2: rejects duplicate folder name with error', function () {
        Folder::create(['name' => 'Tech', 'slug' => 'tech']);

        $this->artisan('rss:folder:create', ['name' => 'Tech'])
            ->assertFailed()
            ->expectsOutputToContain('already exists');
    });
});

// ============================================================================
// US-001: Subscribe to a feed by URL
// ============================================================================

describe('US-001: Subscribe to a feed by URL', function () {
    it('AC1: creates feed from valid RSS 2.0 URL with title and site URL', function () {
        Http::fake([
            'example.com/*' => Http::response(<<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <rss version="2.0">
                    <channel>
                        <title>Test Blog</title>
                        <link>https://example.com</link>
                        <description>A blog</description>
                        <item>
                            <title>Post</title>
                            <link>https://example.com/post</link>
                            <guid>post-1</guid>
                            <pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate>
                        </item>
                    </channel>
                </rss>
                XML),
        ]);

        $this->artisan('rss:feed:add', ['url' => 'https://example.com/feed.xml'])
            ->expectsQuestion('Fetch articles now?', false)
            ->assertSuccessful();

        $feed = Feed::first();
        expect($feed->title)->toBe('Test Blog');
        expect($feed->url)->toBe('https://example.com/feed.xml');
        expect($feed->site_url)->toBe('https://example.com');
        expect($feed->description)->toBe('A blog');
    });

    it('AC2: creates feed from valid Atom URL', function () {
        Http::fake([
            'atom.example.com/*' => Http::response(<<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <feed xmlns="http://www.w3.org/2005/Atom">
                    <title>Atom Blog</title>
                    <link href="https://atom.example.com" rel="alternate"/>
                    <id>urn:uuid:feed-1</id>
                    <entry>
                        <title>Atom Post</title>
                        <link href="https://atom.example.com/post" rel="alternate"/>
                        <id>urn:uuid:post-1</id>
                        <updated>2026-05-04T10:00:00Z</updated>
                    </entry>
                </feed>
                XML),
        ]);

        $this->artisan('rss:feed:add', ['url' => 'https://atom.example.com/feed.atom'])
            ->expectsQuestion('Fetch articles now?', false)
            ->assertSuccessful();

        $feed = Feed::first();
        expect($feed->title)->toBe('Atom Blog');
        expect($feed->url)->toBe('https://atom.example.com/feed.atom');
    });

    it('AC3: shows error for invalid URL (not a feed)', function () {
        Http::fake([
            'bad.example.com/*' => Http::response('Not a feed', 200),
        ]);

        $this->artisan('rss:feed:add', ['url' => 'not-a-url'])
            ->assertFailed()
            ->expectsOutputToContain('Invalid URL');
    });

    it('AC4: shows already subscribed for duplicate URL', function () {
        Feed::factory()->create(['url' => 'https://example.com/feed.xml']);

        $this->artisan('rss:feed:add', ['url' => 'https://example.com/feed.xml'])
            ->assertFailed()
            ->expectsOutputToContain('Already subscribed');
    });

    it('AC5: shows error for non-feed URL returning HTML', function () {
        Http::fake([
            'example.com/*' => Http::response('<html><body>Not a feed</body></html>', 200),
        ]);

        $this->artisan('rss:feed:add', ['url' => 'https://example.com/page'])
            ->assertFailed()
            ->expectsOutputToContain('Failed to fetch or parse');
    });
});

// ============================================================================
// US-009: Parse and store articles from RSS 2.0 feeds
// ============================================================================

describe('US-009: Parse RSS 2.0 articles', function () {
    it('AC1: stores articles with all fields (title, URL, content, author, date, cover image)', function () {
        $feed = Feed::factory()->create(['url' => 'https://example.com/feed.xml']);

        Http::fake([
            'example.com/*' => Http::response(<<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
                    <channel>
                        <title>Blog</title>
                        <link>https://example.com</link>
                        <item>
                            <title>Rich Post</title>
                            <link>https://example.com/rich</link>
                            <author>john@example.com (John)</author>
                            <enclosure url="https://example.com/img.jpg" type="image/jpeg" length="12345"/>
                            <pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate>
                            <guid isPermaLink="false">rich-1</guid>
                            <content:encoded><![CDATA[<p>Full <strong>rich</strong> content</p>]]></content:encoded>
                        </item>
                    </channel>
                </rss>
                XML),
        ]);

        $this->artisan('rss:fetch', ['feed' => (string) $feed->id]);

        $article = Article::first();
        expect($article->title)->toBe('Rich Post');
        expect($article->url)->toBe('https://example.com/rich');
        expect($article->content)->toContain('Full');
        expect($article->author)->toBe('john@example.com (John)');
        expect($article->cover_image)->toBe('https://example.com/img.jpg');
        expect($article->published_at)->not->toBeNull();
        expect($article->external_id)->toBe('rich-1');
    });

    it('AC2: skips duplicate articles on re-fetch', function () {
        $feed = Feed::factory()->create(['url' => 'https://example.com/feed.xml']);

        $xml = <<<'XML'
            <?xml version="1.0"?>
            <rss version="2.0"><channel><title>Blog</title><link>https://example.com</link>
                <item><title>Post</title><link>https://example.com/1</link><guid>unique-1</guid><pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate></item>
            </channel></rss>
            XML;

        Http::fake(['example.com/*' => Http::response($xml)]);

        $this->artisan('rss:fetch', ['feed' => (string) $feed->id]);
        $this->artisan('rss:fetch', ['feed' => (string) $feed->id]);

        expect(Article::count())->toBe(1);
    });

    it('AC3: uses current time as fallback when no published date', function () {
        $feed = Feed::factory()->create(['url' => 'https://example.com/feed.xml']);

        Http::fake([
            'example.com/*' => Http::response(<<<'XML'
                <?xml version="1.0"?>
                <rss version="2.0"><channel><title>Blog</title><link>https://example.com</link>
                    <item><title>No Date</title><link>https://example.com/nodate</link><guid>nd-1</guid></item>
                </channel></rss>
                XML),
        ]);

        $this->artisan('rss:fetch', ['feed' => (string) $feed->id]);

        $article = Article::first();
        expect($article->published_at)->not->toBeNull();
        expect($article->published_at->isToday())->toBeTrue();
    });
});

// ============================================================================
// US-010: Parse and store articles from Atom feeds
// ============================================================================

describe('US-010: Parse Atom entries', function () {
    it('AC1: stores Atom entries with all fields', function () {
        $feed = Feed::factory()->create(['url' => 'https://atom.example.com/feed.atom']);

        Http::fake([
            'atom.example.com/*' => Http::response(<<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <feed xmlns="http://www.w3.org/2005/Atom">
                    <title>Atom Blog</title>
                    <link href="https://atom.example.com" rel="alternate"/>
                    <entry>
                        <title>Atom Post</title>
                        <link href="https://atom.example.com/post" rel="alternate"/>
                        <id>urn:uuid:atom-1</id>
                        <published>2026-05-04T10:00:00Z</published>
                        <updated>2026-05-04T10:00:00Z</updated>
                        <author><name>Jane</name></author>
                        <content type="html">&lt;p&gt;Atom content&lt;/p&gt;</content>
                    </entry>
                </feed>
                XML),
        ]);

        $this->artisan('rss:fetch', ['feed' => (string) $feed->id]);

        $article = Article::first();
        expect($article->title)->toBe('Atom Post');
        expect($article->url)->toBe('https://atom.example.com/post');
        expect($article->content)->toContain('Atom content');
        expect($article->author)->toBe('Jane');
        expect($article->external_id)->toBe('urn:uuid:atom-1');
    });

    it('AC2: does not duplicate Atom entries on re-fetch', function () {
        $feed = Feed::factory()->create(['url' => 'https://atom.example.com/feed.atom']);

        $xml = <<<'XML'
            <?xml version="1.0"?>
            <feed xmlns="http://www.w3.org/2005/Atom">
                <title>Blog</title>
                <link href="https://atom.example.com" rel="alternate"/>
                <entry><title>Post</title><link href="https://atom.example.com/1" rel="alternate"/><id>atom-1</id><updated>2026-05-04T10:00:00Z</updated></entry>
            </feed>
            XML;

        Http::fake(['atom.example.com/*' => Http::response($xml)]);

        $this->artisan('rss:fetch', ['feed' => (string) $feed->id]);
        $this->artisan('rss:fetch', ['feed' => (string) $feed->id]);

        expect(Article::count())->toBe(1);
    });
});

// ============================================================================
// US-011: Fetch all feeds command
// ============================================================================

describe('US-011: Fetch all feeds', function () {
    it('AC1: fetches all feeds and displays summary', function () {
        Feed::factory()->create(['url' => 'https://a.com/feed.xml']);
        Feed::factory()->create(['url' => 'https://b.com/feed.xml']);

        $rssXml = <<<'XML'
            <?xml version="1.0"?><rss version="2.0"><channel><title>Blog</title><link>https://a.com</link>
                <item><title>Post</title><link>https://a.com/1</link><guid>a1</guid><pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate></item>
            </channel></rss>
            XML;

        Http::fake([
            'a.com/*' => Http::response($rssXml),
            'b.com/*' => Http::response($rssXml),
        ]);

        $this->artisan('rss:fetch')
            ->assertSuccessful()
            ->expectsOutputToContain('Fetch complete')
            ->expectsOutputToContain('2');
    });

    it('AC2: continues fetching when one feed errors', function () {
        Feed::factory()->create(['url' => 'https://bad.com/feed.xml']);
        Feed::factory()->create(['url' => 'https://good.com/feed.xml']);

        Http::fake([
            'bad.com/*' => Http::response(null, 500),
            'good.com/*' => Http::response(<<<'XML'
                <?xml version="1.0"?><rss version="2.0"><channel><title>Good</title><link>https://good.com</link>
                    <item><title>Good Post</title><link>https://good.com/1</link><guid>g1</guid><pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate></item>
                </channel></rss>
                XML),
        ]);

        $this->artisan('rss:fetch')
            ->assertSuccessful()
            ->expectsOutputToContain('1 new article(s)');

        expect(Article::count())->toBe(1);
    });

    it('AC3: shows message when no feeds exist', function () {
        $this->artisan('rss:fetch')
            ->assertSuccessful()
            ->expectsOutputToContain('No feeds to fetch');
    });
});

// ============================================================================
// US-013: Handle feed errors gracefully
// ============================================================================

describe('US-013: Handle feed errors', function () {
    it('AC1: handles timeout and continues', function () {
        Feed::factory()->create(['url' => 'https://slow.com/feed.xml']);
        Feed::factory()->create(['url' => 'https://fast.com/feed.xml']);

        Http::fake([
            'slow.com/*' => Http::response(null, 408),
            'fast.com/*' => Http::response(<<<'XML'
                <?xml version="1.0"?><rss version="2.0"><channel><title>Fast</title><link>https://fast.com</link>
                    <item><title>Fast Post</title><link>https://fast.com/1</link><guid>f1</guid><pubDate>Mon, 04 May 2026 10:00:00 +0000</pubDate></item>
                </channel></rss>
                XML),
        ]);

        $this->artisan('rss:fetch')
            ->assertSuccessful()
            ->expectsOutputToContain('1 new article(s)');
    });

    it('AC2: handles invalid XML with error', function () {
        $feed = Feed::factory()->create(['url' => 'https://broken.com/feed.xml']);

        Http::fake([
            'broken.com/*' => Http::response('this is not xml at all <><><>', 200),
        ]);

        $this->artisan('rss:fetch', ['feed' => (string) $feed->id])
            ->assertSuccessful()
            ->expectsOutputToContain('✗');
    });

    it('AC3: handles 404 with error', function () {
        $feed = Feed::factory()->create(['url' => 'https://gone.com/feed.xml']);

        Http::fake([
            'gone.com/*' => Http::response(null, 404),
        ]);

        $this->artisan('rss:fetch', ['feed' => (string) $feed->id])
            ->assertSuccessful()
            ->expectsOutputToContain('✗');
    });
});

// ============================================================================
// US-014: Schedule automatic fetching
// ============================================================================

describe('US-014: Schedule automatic fetching', function () {
    it('AC1+AC2: rss:fetch is scheduled hourly in the scheduler', function () {
        $schedule = app(Illuminate\Console\Scheduling\Schedule::class);
        $events = $schedule->events();
        $fetchEvent = collect($events)->first(fn ($event) => str_contains($event->command, 'rss:fetch'));

        expect($fetchEvent)->not->toBeNull('rss:fetch should be scheduled');
        expect($fetchEvent->expression)->toBe('0 * * * *', 'rss:fetch should run hourly');
    });
});

// ============================================================================
// US-015: Today's feeds homepage
// ============================================================================

describe('US-015: Today\'s feeds homepage', function () {
    it('AC1: displays today\'s articles on homepage', function () {
        $feed = Feed::factory()->create(['title' => 'My Blog']);
        Article::factory()->today()->create([
            'feed_id' => $feed->id,
            'title' => 'Today Article',
            'content' => '<p>Full article content here</p>',
        ]);

        $response = $this->get('/');
        $response->assertSuccessful();
        $response->assertSee('Today Article');
        $response->assertSee('My Blog');
    });

    it('AC2: shows empty state when no articles today', function () {
        $this->get('/')
            ->assertSuccessful()
            ->assertSee('No articles on this date');
    });

    it('AC3: article cards show title, feed name, published time, and excerpt', function () {
        $feed = Feed::factory()->create(['title' => 'Test Feed']);
        Article::factory()->today()->create([
            'feed_id' => $feed->id,
            'title' => 'Featured Article',
            'content' => '<p>This is a long excerpt that should be visible in the card view</p>',
        ]);

        $this->get('/')
            ->assertSuccessful()
            ->assertSee('Featured Article')
            ->assertSee('Test Feed')
            ->assertSee('This is a long excerpt');
    });
});

// ============================================================================
// US-016: Date navigation
// ============================================================================

describe('US-016: Date navigation', function () {
    it('AC1: shows previous day articles via date route', function () {
        $feed = Feed::factory()->create();
        Article::factory()->onDate('2026-05-03')->create([
            'feed_id' => $feed->id,
            'title' => 'Yesterday Article',
        ]);

        $this->get('/date/2026-05-03')
            ->assertSuccessful()
            ->assertSee('Yesterday Article');
    });

    it('AC2: shows next day but not beyond today', function () {
        $this->get('/date/2099-01-01')
            ->assertSuccessful();
    });

    it('AC3: date route accepts Y-m-d format', function () {
        $feed = Feed::factory()->create();
        Article::factory()->onDate('2026-04-15')->create([
            'feed_id' => $feed->id,
            'title' => 'April Article',
        ]);

        $this->get('/date/2026-04-15')
            ->assertSuccessful()
            ->assertSee('April Article');
    });

    it('AC4: shows empty state for dates with no articles', function () {
        $this->get('/date/2020-01-01')
            ->assertSuccessful()
            ->assertSee('No articles on this date');
    });

    it('rejects invalid date format', function () {
        $this->get('/date/invalid-date')
            ->assertNotFound();
    });
});

// ============================================================================
// US-017: Category/folder filter
// ============================================================================

describe('US-017: Category/folder filter', function () {
    it('AC1: filters articles by folder', function () {
        $tech = Folder::create(['name' => 'Tech', 'slug' => 'tech']);
        $news = Folder::create(['name' => 'News', 'slug' => 'news']);

        $techFeed = Feed::factory()->inFolder($tech)->create();
        $newsFeed = Feed::factory()->inFolder($news)->create();

        Article::factory()->today()->create(['feed_id' => $techFeed->id, 'title' => 'Tech Article']);
        Article::factory()->today()->create(['feed_id' => $newsFeed->id, 'title' => 'News Article']);

        $this->get('/?folder=tech')
            ->assertSuccessful()
            ->assertSee('Tech Article')
            ->assertDontSee('News Article');
    });

    it('AC2: shows all articles when no filter selected', function () {
        $folder = Folder::create(['name' => 'Tech', 'slug' => 'tech']);
        $feedInFolder = Feed::factory()->inFolder($folder)->create();
        $feedOutside = Feed::factory()->create();

        Article::factory()->today()->create(['feed_id' => $feedInFolder->id, 'title' => 'Folder Article']);
        Article::factory()->today()->create(['feed_id' => $feedOutside->id, 'title' => 'Unfoldered Article']);

        $this->get('/')
            ->assertSuccessful()
            ->assertSee('Folder Article')
            ->assertSee('Unfoldered Article');
    });

    it('AC3: folder filter pills are displayed', function () {
        Folder::create(['name' => 'Tech', 'slug' => 'tech']);
        Folder::create(['name' => 'News', 'slug' => 'news']);

        $this->get('/')
            ->assertSuccessful()
            ->assertSee('Tech')
            ->assertSee('News')
            ->assertSee('All');
    });

    it('folder filter works with date route', function () {
        $folder = Folder::create(['name' => 'Tech', 'slug' => 'tech']);
        $feed = Feed::factory()->inFolder($folder)->create();
        $otherFeed = Feed::factory()->create();

        Article::factory()->onDate('2026-05-03')->create(['feed_id' => $feed->id, 'title' => 'Tech Past']);
        Article::factory()->onDate('2026-05-03')->create(['feed_id' => $otherFeed->id, 'title' => 'Other Past']);

        $this->get('/date/2026-05-03?folder=tech')
            ->assertSuccessful()
            ->assertSee('Tech Past')
            ->assertDontSee('Other Past');
    });
});

// ============================================================================
// US-018: Article card display
// ============================================================================

describe('US-018: Article card display', function () {
    it('AC1: cards show title, feed name, published time, and excerpt', function () {
        $feed = Feed::factory()->create(['title' => 'Design Blog']);
        Article::factory()->today()->create([
            'feed_id' => $feed->id,
            'title' => 'Card Title',
            'content' => '<p>This is the article excerpt that should be visible</p>',
        ]);

        $this->get('/')
            ->assertSuccessful()
            ->assertSee('Card Title')
            ->assertSee('Design Blog')
            ->assertSee('article excerpt');
    });

    it('AC2: cover image is rendered in card when present', function () {
        $feed = Feed::factory()->create();
        Article::factory()->today()->create([
            'feed_id' => $feed->id,
            'cover_image' => 'https://example.com/photo.jpg',
        ]);

        $this->get('/')
            ->assertSuccessful()
            ->assertSee('https://example.com/photo.jpg');
    });

    it('AC3: card is clickable (has onclick with article ID)', function () {
        $feed = Feed::factory()->create();
        $article = Article::factory()->today()->create(['feed_id' => $feed->id]);

        $this->get('/')
            ->assertSuccessful()
            ->assertSee("openArticle({$article->id})");
    });
});

// ============================================================================
// US-019: Article reading view
// ============================================================================

describe('US-019: Article reading view', function () {
    it('AC1: article JSON endpoint returns full content', function () {
        $feed = Feed::factory()->create(['title' => 'Source Blog']);
        $article = Article::factory()->today()->create([
            'feed_id' => $feed->id,
            'title' => 'Full Article Title',
            'content' => '<p>Full article body with <strong>formatting</strong></p>',
            'author' => 'Jane Doe',
        ]);

        $this->getJson("/article/{$article->id}")
            ->assertSuccessful()
            ->assertJsonPath('title', 'Full Article Title')
            ->assertJsonPath('content', '<p>Full article body with <strong>formatting</strong></p>')
            ->assertJsonPath('author', 'Jane Doe')
            ->assertJsonPath('feed.title', 'Source Blog');
    });

    it('AC3: JSON includes source URL for "read original" link', function () {
        $feed = Feed::factory()->create(['site_url' => 'https://example.com']);
        $article = Article::factory()->today()->create([
            'feed_id' => $feed->id,
            'url' => 'https://example.com/original-article',
        ]);

        $this->getJson("/article/{$article->id}")
            ->assertSuccessful()
            ->assertJsonPath('url', 'https://example.com/original-article');
    });

    it('returns 404 for non-existent article', function () {
        $this->getJson('/article/99999')
            ->assertNotFound();
    });
});
