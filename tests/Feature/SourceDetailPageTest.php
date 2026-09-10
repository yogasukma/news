<?php

use App\Models\Article;
use App\Models\Feed;
use Carbon\Carbon;

describe('US-045: source detail page route, header, and not-found handling', function () {
    it('renders the source detail page for a valid feed id', function () {
        $feed = Feed::factory()->create([
            'title' => 'Example Blog',
            'favicon_url' => 'https://example.com/favicon.ico',
            'site_url' => 'https://example.com',
        ]);

        $response = $this->get(route('sources.show', $feed));

        $response->assertSuccessful();
        $response->assertSee('Example Blog');
        $response->assertSee('favicon.ico');
    });

    it('returns 404 for a non-existent feed id', function () {
        $this->get('/sources/999999')->assertNotFound();
    });

    it('shows the site URL as a new-tab link when the feed has a valid site_url', function () {
        $feed = Feed::factory()->create([
            'title' => 'Linked Blog',
            'site_url' => 'https://example.com/blog',
        ]);

        $content = $this->get(route('sources.show', $feed))->assertSuccessful()->getContent();

        expect($content)->toContain('href="https://example.com/blog"');
        expect($content)->toContain('target="_blank"');
        expect($content)->toContain('rel="noopener noreferrer"');
        // External link must NOT carry data-spa (should open normally in a new tab)
        expect($content)->not->toContain('data-spa href="https://example.com/blog"');
    });

    it('omits the external site link when the feed has no valid site_url', function () {
        $feed = Feed::factory()->create([
            'title' => 'Bare Blog',
            'site_url' => null,
        ]);

        // Fragment view avoids layout/modal noise; the only new-tab link in this
        // content would be the (omitted) external site link
        $response = $this->get(route('sources.show', $feed).'?fragment=1')->assertSuccessful();

        $response->assertSee('Bare Blog');
        expect($response->getContent())->not->toContain('target="_blank"');
        expect($response->getContent())->not->toContain('rel="noopener noreferrer"');
    });

    it('provides a back to sources link using SPA navigation', function () {
        $feed = Feed::factory()->create();

        $content = $this->get(route('sources.show', $feed))->assertSuccessful()->getContent();

        expect($content)->toContain('Back to sources');
        expect($content)->toContain('href="'.route('sources').'"');
        expect($content)->toContain('data-spa');
    });

    it('renders the same content via SPA fragment without a full page reload', function () {
        $feed = Feed::factory()->create(['title' => 'Fragment Source']);

        $response = $this->get(route('sources.show', $feed).'?fragment=1');

        $response->assertSuccessful();
        $response->assertSee('Fragment Source');
        $response->assertDontSee('<!DOCTYPE html>', false);
        $response->assertDontSee('RSS Reader', false);
    });
});

describe('US-046: source detail page paginated article list', function () {
    it('lists articles newest first', function () {
        $feed = Feed::factory()->create();

        $older = Article::factory()->create(['feed_id' => $feed->id, 'title' => 'Older Post', 'published_at' => now()->subDays(2)]);
        $newer = Article::factory()->create(['feed_id' => $feed->id, 'title' => 'Newer Post', 'published_at' => now()]);

        $content = $this->get(route('sources.show', $feed))->assertSuccessful()->getContent();

        expect(strpos($content, 'Newer Post'))->toBeLessThan(strpos($content, 'Older Post'));
    });

    it('only lists articles belonging to that source', function () {
        $feed = Feed::factory()->create();
        $otherFeed = Feed::factory()->create();

        Article::factory()->create(['feed_id' => $feed->id, 'title' => 'My Source Article']);
        Article::factory()->create(['feed_id' => $otherFeed->id, 'title' => 'Other Source Article']);

        $response = $this->get(route('sources.show', $feed));

        $response->assertSuccessful()->assertSee('My Source Article');
        $response->assertDontSee('Other Source Article');
    });

    it('paginates article lists 30 per page', function () {
        $feed = Feed::factory()->create();
        Article::factory()->count(35)->create(['feed_id' => $feed->id]);

        $pageOne = $this->get(route('sources.show', $feed))->assertSuccessful()->getContent();
        $pageTwo = $this->get(route('sources.show', $feed).'?page=2')->assertSuccessful()->getContent();

        expect($pageOne)->toContain('Page 1 of 2');
        expect($pageOne)->toContain('data-spa');
        expect($pageTwo)->toContain('Page 2 of 2');
        // Page 2 holds the remaining 5 articles — each card carries data-article-id
        expect(substr_count($pageTwo, 'data-article-id='))->toBe(5);
    });

    it('shows date and time on article cards', function () {
        $feed = Feed::factory()->create();
        $publishedAt = Carbon::parse('2026-05-04 15:45:00');
        Article::factory()->create(['feed_id' => $feed->id, 'published_at' => $publishedAt]);

        $this->get(route('sources.show', $feed))
            ->assertSuccessful()
            ->assertSee($publishedAt->format('M j, g:i A'));
    });

    it('wires article cards to open in the reading modal', function () {
        $feed = Feed::factory()->create();
        $article = Article::factory()->create(['feed_id' => $feed->id]);

        $this->get(route('sources.show', $feed))
            ->assertSuccessful()
            ->assertSee('onclick="openArticle('.$article->id.')"', false);
    });

    it('shows an empty state when the source has no articles', function () {
        $feed = Feed::factory()->create();

        $this->get(route('sources.show', $feed))
            ->assertSuccessful()
            ->assertSee('No articles yet.');
    });

    it('shows the article count in the header', function () {
        $feed = Feed::factory()->create();
        Article::factory()->count(3)->create(['feed_id' => $feed->id]);

        $this->get(route('sources.show', $feed))
            ->assertSuccessful()
            ->assertSee('3 articles');
    });
});
