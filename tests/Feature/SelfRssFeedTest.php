<?php

use App\Models\Article;
use App\Models\Feed;

describe('US-051: public self RSS feed with pagination', function () {
    it('returns an RSS feed with no login and an XML content type', function () {
        $feed = Feed::factory()->create();
        Article::factory()->create(['feed_id' => $feed->id]);

        $response = $this->get('/rss');

        $response->assertSuccessful();
        expect($response->headers->get('Content-Type'))->toContain('application/rss+xml');
        expect($response->getContent())->toContain('<rss version="2.0"');
    });

    it('orders articles newest first with channel info', function () {
        $feed = Feed::factory()->create();
        $older = Article::factory()->create(['feed_id' => $feed->id, 'title' => 'Older Entry', 'published_at' => now()->subDays(2)]);
        $newer = Article::factory()->create(['feed_id' => $feed->id, 'title' => 'Newer Entry', 'published_at' => now()]);

        $content = $this->get('/rss')->assertSuccessful()->getContent();

        expect($content)->toContain('<channel>');
        expect($content)->toContain(url('/'));
        expect(strpos($content, 'Newer Entry'))->toBeLessThan(strpos($content, 'Older Entry'));
    });

    it('shows title, source link, date, source name, and full content per item', function () {
        $feed = Feed::factory()->create(['title' => 'Source Blog']);
        $article = Article::factory()->create([
            'feed_id' => $feed->id,
            'title' => 'Full Item Article',
            'url' => 'https://example.com/original-post',
            'content' => '<p>Full body content here</p>',
            'published_at' => now(),
        ]);

        $content = $this->get('/rss')->assertSuccessful()->getContent();

        expect($content)->toContain('Full Item Article');
        expect($content)->toContain('https://example.com/original-post');
        expect($content)->toContain('Source Blog');
        expect($content)->toContain('Full body content here');
        expect($content)->toContain($article->published_at->toRfc2822String());
    });

    it('limits page one to the 100 newest articles', function () {
        $feed = Feed::factory()->create();
        Article::factory()->count(105)->create(['feed_id' => $feed->id]);

        $content = $this->get('/rss')->assertSuccessful()->getContent();

        expect(substr_count($content, '<item>'))->toBe(100);
    });

    it('shows the next articles on page two', function () {
        $feed = Feed::factory()->create();
        $oldest = Article::factory()->create(['feed_id' => $feed->id, 'title' => 'Oldest Paged Article', 'published_at' => now()->subDays(10)]);
        Article::factory()->count(100)->create(['feed_id' => $feed->id, 'published_at' => now()]);

        $pageTwo = $this->get('/rss?page=2')->assertSuccessful()->getContent();

        expect($pageTwo)->toContain('Oldest Paged Article');
        expect(substr_count($pageTwo, '<item>'))->toBe(1);
    });

    it('returns valid XML even with no articles', function () {
        $content = $this->get('/rss')->assertSuccessful()->getContent();

        expect(simplexml_load_string($content))->not->toBeFalse();
        expect(substr_count($content, '<item>'))->toBe(0);
    });

    it('handles an out-of-range page without an error', function () {
        $feed = Feed::factory()->create();
        Article::factory()->create(['feed_id' => $feed->id]);

        $content = $this->get('/rss?page=9999')->assertSuccessful()->getContent();

        expect(simplexml_load_string($content))->not->toBeFalse();
        expect(substr_count($content, '<item>'))->toBe(0);
    });

    it('keeps XML valid when content contains a CDATA terminator', function () {
        $feed = Feed::factory()->create();
        Article::factory()->create(['feed_id' => $feed->id, 'content' => '<p>tricky ]]> content</p>']);

        $content = $this->get('/rss')->assertSuccessful()->getContent();

        expect(simplexml_load_string($content))->not->toBeFalse();
    });
});

describe('US-052: advertise self RSS feed for auto-discovery', function () {
    it('includes a discovery link on the homepage head', function () {
        $content = $this->get('/')->assertSuccessful()->getContent();

        expect($content)->toContain('rel="alternate"');
        expect($content)->toContain('type="application/rss+xml"');
        expect($content)->toContain(url('/rss'));
    });

    it('opens the feed when the discovery URL is followed', function () {
        $feed = Feed::factory()->create();
        Article::factory()->create(['feed_id' => $feed->id]);

        $this->get(url('/rss'))->assertSuccessful()->assertSee('<rss', false);
    });
});
