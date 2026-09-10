<?php

use App\Models\Article;
use App\Models\Feed;
use App\Models\Folder;
use Carbon\Carbon;

describe('US-040: sources page', function () {
    it('shows a separator and Sources link right after the date picker on the article page', function () {
        $feed = Feed::factory()->create();
        Article::factory()->today()->create(['feed_id' => $feed->id]);

        $content = $this->get('/')->assertSuccessful()->getContent();

        // Date picker, then separator, then Sources link (in document order)
        $datePickerPos = strpos($content, 'data-spa-date');
        $separatorPos = strpos($content, '>|</span>', $datePickerPos);
        $sourcesPos = strpos($content, 'Sources', $separatorPos);

        expect($datePickerPos)->not->toBe(false);
        expect($separatorPos)->not->toBe(false);
        expect($sourcesPos)->not->toBe(false);
        expect($sourcesPos)->toBeGreaterThan($separatorPos);
        expect($content)->toContain('href="'.route('sources').'"');
    });

    it('lists all feeds regardless of the selected date', function () {
        $dateFeed = Feed::factory()->create(['title' => 'Today Feed']);
        $otherFeed = Feed::factory()->create(['title' => 'Other Day Feed']);

        Article::factory()->today()->create(['feed_id' => $dateFeed->id]);
        Article::factory()->onDate('2026-05-01')->create(['feed_id' => $otherFeed->id]);

        $this->get('/sources')
            ->assertSuccessful()
            ->assertSee('Today Feed')
            ->assertSee('Other Day Feed');
    });

    it('shows favicon and feed name on the left and last fetched time on the right', function () {
        $feed = Feed::factory()->create([
            'title' => 'Example Blog',
            'favicon_url' => 'https://example.com/favicon.ico',
            'last_fetched_at' => now()->subHours(3),
        ]);

        $response = $this->get('/sources');

        $response->assertSuccessful();
        $content = $response->getContent();

        // favicon + name left, time right (name appears before the time in document order)
        $namePos = strpos($content, 'Example Blog');
        $timePos = strpos($content, $feed->last_fetched_at->diffForHumans());
        $faviconPos = strpos($content, 'favicon.ico');

        expect($namePos)->not->toBe(false);
        expect($timePos)->not->toBe(false);
        expect($faviconPos)->not->toBe(false);
        expect($faviconPos)->toBeLessThan($namePos);
        expect($namePos)->toBeLessThan($timePos);
    });

    it('sorts feeds by last fetched time descending (most recently fetched first)', function () {
        $older = Feed::factory()->create([
            'title' => 'Older Feed',
            'last_fetched_at' => now()->subDays(2),
        ]);
        $newer = Feed::factory()->create([
            'title' => 'Newer Feed',
            'last_fetched_at' => now()->subHours(1),
        ]);

        $content = $this->get('/sources')->assertSuccessful()->getContent();

        expect(strpos($content, 'Newer Feed'))->toBeLessThan(strpos($content, 'Older Feed'));
    });

    it('places feeds that have never been fetched at the bottom', function () {
        $fetched = Feed::factory()->create([
            'title' => 'Fetched Feed',
            'last_fetched_at' => now()->subHours(1),
        ]);
        $never = Feed::factory()->create([
            'title' => 'Never Feed',
            'last_fetched_at' => null,
        ]);
        $older = Feed::factory()->create([
            'title' => 'Older Feed',
            'last_fetched_at' => now()->subDays(2),
        ]);

        $content = $this->get('/sources')->assertSuccessful()->getContent();

        expect(strpos($content, 'Fetched Feed'))->toBeLessThan(strpos($content, 'Older Feed'));
        expect(strpos($content, 'Older Feed'))->toBeLessThan(strpos($content, 'Never Feed'));
    });

    it('shows Never for feeds without fetch history', function () {
        Feed::factory()->create(['last_fetched_at' => null]);

        $this->get('/sources')
            ->assertSuccessful()
            ->assertSee('Never');
    });

    it('renders the same content via SPA fragment without a full page reload', function () {
        $feed = Feed::factory()->create(['title' => 'Fragment Feed', 'last_fetched_at' => now()]);

        $response = $this->get('/sources?fragment=1');

        $response->assertSuccessful();
        $response->assertSee('Fragment Feed');
        $response->assertDontSee('<!DOCTYPE html>', false);
        $response->assertDontSee('RSS Reader', false);
    });

    it('shows an empty state when there are no feeds', function () {
        $this->get('/sources')
            ->assertSuccessful()
            ->assertSee('No sources yet.');
    });

    it('shows feed count in the header', function () {
        Feed::factory()->count(3)->create();

        $this->get('/sources')
            ->assertSuccessful()
            ->assertSee('3 sources');
    });

    it('titles the page RSS Sources', function () {
        Feed::factory()->create();

        $this->get('/sources')
            ->assertSuccessful()
            ->assertSee('RSS Sources');
    });

    it('provides a back to feeds link using SPA navigation', function () {
        Feed::factory()->create();

        $response = $this->get('/sources');

        $content = $response->getContent();

        expect($content)->toContain('href="/"');
        $backPos = strpos($content, 'Back to feeds');

        expect($backPos)->not->toBe(false);
        expect($backPos)->toBeLessThan(strpos($content, 'RSS Sources'));
    });

    it('links each source row to its source detail page via SPA navigation', function () {
        $feed = Feed::factory()->create([
            'title' => 'Clickable Feed',
            'site_url' => 'https://example.com',
            'last_fetched_at' => now()->subHours(1),
        ]);

        $response = $this->get('/sources');

        $response->assertSuccessful();
        $content = $response->getContent();

        $hrefPos = strpos($content, 'href="'.route('sources.show', $feed).'"');
        $titlePos = strpos($content, 'Clickable Feed');
        $timePos = strpos($content, $feed->last_fetched_at->diffForHumans());

        expect($hrefPos)->not->toBe(false);
        expect($titlePos)->not->toBe(false);
        // The row link wraps the feed name (href appears before the title)
        expect($hrefPos)->toBeLessThan($titlePos);
        // The fetch time stays outside the link (appears after the title)
        expect($titlePos)->toBeLessThan($timePos);
        // Internal SPA link — no external new-tab behavior on the row
        expect($content)->toContain('data-spa');
        expect($content)->not->toContain('href="https://example.com"');
        // The detail-page row link itself is not a new-tab link
        // (other target="_blank" instances in the response belong to the shared modal layout)
        expect($content)->not->toContain(route('sources.show', $feed).'" target="_blank"');
    });

    it('renders sources without a site homepage as clickable detail links', function () {
        $feed = Feed::factory()->create([
            'title' => 'No Site Feed',
            'site_url' => null,
        ]);

        $response = $this->get('/sources?fragment=1');

        $response->assertSuccessful()->assertSee('No Site Feed');
        // No external http(s) anchor exists anywhere in the fragment (only internal detail-page links)
        expect($response->getContent())->toContain(route('sources.show', $feed));
    });

    it('includes the folder name next to the feed title when assigned', function () {
        $folder = Folder::create(['name' => 'Tech', 'slug' => 'tech']);
        $feed = Feed::factory()->inFolder($folder)->create(['title' => 'Tech Blog']);
        Article::factory()->today()->create(['feed_id' => $feed->id]);

        $this->get('/sources')
            ->assertSuccessful()
            ->assertSee('Tech Blog')
            ->assertSee('Tech');
    });
});

describe('US-040: relative fetch time formatting', function () {
    it('renders diffForHumans for feeds with fetch history', function () {
        $fetchedAt = Carbon::parse('2026-05-01 10:00:00');
        $feed = Feed::factory()->create(['last_fetched_at' => $fetchedAt]);

        $this->get('/sources')
            ->assertSuccessful()
            ->assertSee($fetchedAt->diffForHumans());
    });
});
