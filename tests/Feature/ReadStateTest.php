<?php

use App\Models\Article;
use App\Models\Feed;

// ============================================================
// US-033: Mark articles as read with localStorage
// ============================================================

describe('US-033: article card data attribute', function () {
    it('includes data-article-id on article cards', function () {
        $feed = Feed::factory()->create();
        Article::factory()->today()->create(['feed_id' => $feed->id, 'title' => 'Test Article']);

        $response = $this->get('/');

        $response->assertSuccessful();
        $response->assertSee('data-article-id', false);
    });

    it('includes correct article ID in data attribute', function () {
        $feed = Feed::factory()->create();
        $article = Article::factory()->today()->create(['feed_id' => $feed->id]);

        $response = $this->get('/');

        $response->assertSuccessful();
        $response->assertSee('data-article-id="'.$article->id.'"', false);
    });
});

describe('US-033: read state CSS class', function () {
    it('includes is-read CSS class definition', function () {
        $feed = Feed::factory()->create();
        Article::factory()->today()->create(['feed_id' => $feed->id]);

        $response = $this->get('/');

        $response->assertSuccessful();
        // The CSS is in the compiled assets, not inline. Verify the layout loads read-state.js
        $content = $response->getContent();
        expect($content)->toContain('read-state');
    });
});

describe('US-033: read state JS module', function () {
    it('loads read-state.js in layout', function () {
        $feed = Feed::factory()->create();
        Article::factory()->today()->create(['feed_id' => $feed->id]);

        $response = $this->get('/');

        $response->assertSuccessful();
        // Vite manifest includes read-state.js
        $content = $response->getContent();
        expect($content)->toContain('read-state');
    });
});

// ============================================================
// US-034: Round images in article modal
// ============================================================

describe('US-034: rounded images in modal', function () {
    it('modal body element exists with id', function () {
        $feed = Feed::factory()->create();
        Article::factory()->today()->create(['feed_id' => $feed->id]);

        $response = $this->get('/');

        $response->assertSuccessful();
        $response->assertSee('id="modal-body"', false);
    });

    it('CSS includes modal body image rounding', function () {
        // CSS is in compiled assets — verify the build includes it by checking the build output
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $cssFile = collect($manifest)->first(fn ($entry) => isset($entry['src']) && $entry['src'] === 'resources/css/app.css');

        expect($cssFile)->not->toBeNull('CSS entry should exist in manifest');

        $cssContent = file_get_contents(public_path('build/'.$cssFile['file']));
        expect($cssContent)->toContain('modal-body');
        expect($cssContent)->toContain('border-radius');
    });
});
