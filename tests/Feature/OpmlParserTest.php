<?php

use App\Services\OpmlParser;

beforeEach(function () {
    $this->parser = new OpmlParser;
});

it('parses valid OPML with folders and feeds', function () {
    $xml = file_get_contents(base_path('tests/fixtures/opml/valid.opml'));
    $result = $this->parser->parse($xml);

    expect($result['title'])->toBe('Test Subscriptions');
    expect($result['folders'])->toHaveCount(2);
    expect($result['feeds'])->toHaveCount(7); // 3 Dev + 2 Blog + 2 uncategorized

    // Check folder structure
    expect($result['folders'][0])->toBe(['name' => 'Dev', 'slug' => 'dev']);
    expect($result['folders'][1])->toBe(['name' => 'Blog', 'slug' => 'blog']);

    // Check a foldered feed
    $laravelFeed = collect($result['feeds'])->first(fn ($f) => str_contains($f['title'], 'Laravel'));
    expect($laravelFeed['url'])->toBe('https://laravel-news.com/feed');
    expect($laravelFeed['folder_name'])->toBe('Dev');
    expect($laravelFeed['site_url'])->toBe('https://laravel-news.com');

    // Check an uncategorized feed
    $hnFeed = collect($result['feeds'])->first(fn ($f) => str_contains($f['title'], 'Hacker News'));
    expect($hnFeed['folder_name'])->toBeNull();
});

it('parses empty OPML', function () {
    $xml = file_get_contents(base_path('tests/fixtures/opml/empty.opml'));
    $result = $this->parser->parse($xml);

    expect($result['title'])->toBe('Empty OPML');
    expect($result['folders'])->toHaveCount(0);
    expect($result['feeds'])->toHaveCount(0);
});

it('parses OPML with no folders (flat feeds)', function () {
    $xml = file_get_contents(base_path('tests/fixtures/opml/no-folders.opml'));
    $result = $this->parser->parse($xml);

    expect($result['folders'])->toHaveCount(0);
    expect($result['feeds'])->toHaveCount(3);

    foreach ($result['feeds'] as $feed) {
        expect($feed['folder_name'])->toBeNull();
    }
});

it('handles special characters and emoji', function () {
    $xml = file_get_contents(base_path('tests/fixtures/opml/special-chars.opml'));
    $result = $this->parser->parse($xml);

    expect($result['title'])->toContain('&');
    expect($result['folders'])->toHaveCount(1);
    expect($result['folders'][0]['name'])->toBe('Tech & Dev');

    $emojiFeed = collect($result['feeds'])->first(fn ($f) => str_contains($f['title'], '✍'));
    expect($emojiFeed)->not->toBeNull();
    expect($emojiFeed['folder_name'])->toBe('Tech & Dev');

    $flowerFeed = collect($result['feeds'])->first(fn ($f) => str_contains($f['title'], '💐'));
    expect($flowerFeed)->not->toBeNull();
    expect($flowerFeed['folder_name'])->toBeNull();
});

it('throws exception for invalid XML', function () {
    $xml = file_get_contents(base_path('tests/fixtures/opml/invalid.xml'));
    $this->parser->parse($xml);
})->throws(Exception::class, 'Failed to parse OPML');

it('extracts feed title from text attribute when title is missing', function () {
    $xml = <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <opml version="1.0">
        <head><title>Test</title></head>
        <body>
            <outline type="rss" text="Feed From Text" xmlUrl="https://example.com/feed"/>
        </body>
    </opml>
    XML;

    $result = $this->parser->parse($xml);
    expect($result['feeds'][0]['title'])->toBe('Feed From Text');
});

it('handles feeds without htmlUrl', function () {
    $xml = <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <opml version="1.0">
        <head><title>Test</title></head>
        <body>
            <outline type="rss" text="No HtmlUrl" title="No HtmlUrl" xmlUrl="https://example.com/feed"/>
        </body>
    </opml>
    XML;

    $result = $this->parser->parse($xml);
    expect($result['feeds'][0]['site_url'])->toBeNull();
});
