<?php

use App\Models\Article;

describe('Article::normalizeUrl', function () {
    it('strips utm_* tracking parameters but keeps other query parameters', function () {
        expect(Article::normalizeUrl('https://example.com/post?utm_source=rss&utm_medium=feed&id=5'))
            ->toBe('https://example.com/post?id=5');
    });

    it('strips known campaign and analytics parameters', function () {
        expect(Article::normalizeUrl('https://example.com/post?fbclid=abc&gclid=def&mc_cid=ghi&mc_eid=jkl&ref=src&source=rss'))
            ->toBe('https://example.com/post');
    });

    it('strips the fragment', function () {
        expect(Article::normalizeUrl('https://example.com/post#comments'))
            ->toBe('https://example.com/post');
    });

    it('preserves the path, port, and remaining query string', function () {
        expect(Article::normalizeUrl('https://example.com:8443/blog/post.html?utm_source=rss&page=2'))
            ->toBe('https://example.com:8443/blog/post.html?page=2');
    });

    it('returns the input unchanged when it cannot be parsed', function () {
        expect(Article::normalizeUrl('not-a-url'))
            ->toBe('not-a-url');
    });
});
