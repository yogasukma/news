<?php

it('has a valid web app manifest file', function () {
    $path = public_path('manifest.json');

    expect(file_exists($path))->toBeTrue('manifest.json exists in public/');

    $manifest = json_decode(file_get_contents($path), true);

    expect($manifest)->not->toBeNull('manifest.json is valid JSON');
    expect($manifest)->toHaveKey('name', 'RSS Reader');
    expect($manifest)->toHaveKey('short_name', 'RSS');
    expect($manifest)->toHaveKey('start_url', '/');
    expect($manifest)->toHaveKey('display', 'standalone');
    expect($manifest)->toHaveKey('theme_color');
    expect($manifest)->toHaveKey('icons');
    expect($manifest['icons'])->toHaveCount(2);

    $sizes = collect($manifest['icons'])->pluck('sizes')->toArray();
    expect($sizes)->toContain('192x192');
    expect($sizes)->toContain('512x512');
});

it('includes manifest link in layout', function () {
    $response = $this->get('/');

    $response->assertSuccessful();
    $content = $response->content();

    expect($content)->toContain('rel="manifest"');
    expect($content)->toContain('/manifest.json');
    expect($content)->toContain('apple-touch-icon');
    expect($content)->toContain('/icons/icon-192.png');
});

it('has PWA icon files', function () {
    expect(file_exists(public_path('icons/icon-192.png')))->toBeTrue('icon-192.png exists');
    expect(file_exists(public_path('icons/icon-512.png')))->toBeTrue('icon-512.png exists');
});

it('has service worker file', function () {
    $path = public_path('sw.js');

    expect(file_exists($path))->toBeTrue('sw.js exists in public/');

    $content = file_get_contents($path);

    expect($content)->toContain('CACHE_NAME');
    expect($content)->toContain('install');
    expect($content)->toContain('activate');
    expect($content)->toContain('fetch');
});

it('registers search route', function () {
    $response = $this->get('/search');

    $response->assertSuccessful();
});
