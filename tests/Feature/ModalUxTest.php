<?php

use App\Models\Article;
use App\Models\Feed;

// ============================================================
// US-041: Close article modal by clicking outside content
// ============================================================

describe('US-041: modal overlay markup', function () {
    it('exposes a modal overlay wrapper covering the viewport', function () {
        $feed = Feed::factory()->create();
        Article::factory()->today()->create(['feed_id' => $feed->id]);

        $this->get('/')
            ->assertSuccessful()
            ->assertSee('id="modal-overlay"', false)
            ->assertSee('id="modal-content"', false)
            ->assertSee('id="modal-close"', false);
    });

    it('wires the overlay click-to-dismiss listener in app.js', function () {
        $source = file_get_contents(base_path('resources/js/app.js'));

        expect($source)->toContain("document.getElementById('modal-overlay').addEventListener('click'");
        // Clicks inside the card or on the close button must not close the modal
        expect($source)->toContain("e.target.closest('#modal-content')");
        expect($source)->toContain("e.target.closest('#modal-close')");
    });

    it('no longer relies on the dead modal-backdrop click listener', function () {
        $source = file_get_contents(base_path('resources/js/app.js'));

        expect($source)->not->toContain('modalBackdrop.addEventListener');
    });

    it('keeps the Escape key handler for closing the modal', function () {
        $source = file_get_contents(base_path('resources/js/app.js'));

        expect($source)->toContain("e.key === 'Escape'");
    });
});

// ============================================================
// US-042: Open modal content links in a new tab
// ============================================================

describe('US-042: modal content links open in a new tab', function () {
    it('processes injected modal-body links to add target and rel attributes', function () {
        $source = file_get_contents(base_path('resources/js/app.js'));

        expect($source)->toContain("modalBody.querySelectorAll('a').forEach");
        expect($source)->toContain("a.target = '_blank'");
        expect($source)->toContain("a.rel = 'noopener noreferrer'");
    });

    it('keeps the Read original footer link with new-tab behavior', function () {
        $feed = Feed::factory()->create();
        Article::factory()->today()->create(['feed_id' => $feed->id]);

        $this->get('/')
            ->assertSuccessful()
            ->assertSee('id="modal-original-link"', false)
            ->assertSee('target="_blank"', false)
            ->assertSee('rel="noopener noreferrer"', false);
    });
});

// ============================================================
// US-043: Responsive full-width images in modal content
// ============================================================

describe('US-043: responsive images in modal content', function () {
    it('renders modal-body images at full width with auto height and preserved rounding', function () {
        $css = file_get_contents(base_path('resources/css/app.css'));

        // Extract the #modal-body img rule block
        preg_match('/#modal-body img\s*{(.*?)}/s', $css, $matches);

        expect($matches)->toHaveCount(2);
        expect($matches[1])->toContain('width: 100%');
        expect($matches[1])->toContain('max-width: 100%');
        expect($matches[1])->toContain('height: auto');
        expect($matches[1])->toContain('border-radius: 0.5rem');
    });
});
