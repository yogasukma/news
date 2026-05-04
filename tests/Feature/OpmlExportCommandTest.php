<?php

use App\Models\Feed;
use App\Models\Folder;

it('exports feeds organized by folder', function () {
    $folder = Folder::create(['name' => 'Tech', 'slug' => 'tech']);
    Feed::factory()->create(['title' => 'Feed A', 'url' => 'https://a.com/feed', 'site_url' => 'https://a.com', 'folder_id' => $folder->id]);
    Feed::factory()->create(['title' => 'Feed B', 'url' => 'https://b.com/feed', 'folder_id' => null]);

    $exportPath = tempnam(sys_get_temp_dir(), 'opml_export_').'.opml';

    $this->artisan('rss:opml:export', ['file' => $exportPath])
        ->expectsOutputToContain('Exported 2 feed(s)')
        ->assertSuccessful();

    $content = file_get_contents($exportPath);

    // Verify valid XML
    $xml = simplexml_load_string($content);
    expect($xml)->not->toBeFalse();

    // Verify structure
    expect($xml->getName())->toBe('opml');
    expect((string) $xml->head->title)->toBe('RSS Reader Export');

    // Count outlines — should have 1 folder outline + 1 uncategorized feed
    $bodyOutlines = $xml->body->outline;
    $folderOutlines = [];
    $feedOutlines = [];

    foreach ($bodyOutlines as $outline) {
        $xmlUrl = (string) ($outline['xmlUrl'] ?? '');
        if ($xmlUrl !== '') {
            $feedOutlines[] = $outline;
        } else {
            $folderOutlines[] = $outline;
        }
    }

    expect($folderOutlines)->toHaveCount(1);
    expect($feedOutlines)->toHaveCount(1); // uncategorized
    expect((string) $folderOutlines[0]['text'])->toBe('Tech');

    // Verify feed inside folder
    $childFeeds = $folderOutlines[0]->outline;
    expect($childFeeds)->toHaveCount(1);
    expect((string) $childFeeds[0]['xmlUrl'])->toBe('https://a.com/feed');

    // Cleanup
    unlink($exportPath);
});

it('exports empty OPML when no feeds exist', function () {
    $exportPath = tempnam(sys_get_temp_dir(), 'opml_export_').'.opml';

    $this->artisan('rss:opml:export', ['file' => $exportPath])
        ->expectsOutputToContain('Exported 0 feed(s)')
        ->assertSuccessful();

    $content = file_get_contents($exportPath);
    $xml = simplexml_load_string($content);

    expect($xml)->not->toBeFalse();
    expect($xml->body->outline)->toHaveCount(0);

    unlink($exportPath);
});

it('exports feed with all attributes', function () {
    Feed::factory()->create([
        'title' => 'Test Feed',
        'url' => 'https://example.com/feed.xml',
        'site_url' => 'https://example.com',
        'folder_id' => null,
    ]);

    $exportPath = tempnam(sys_get_temp_dir(), 'opml_export_').'.opml';

    $this->artisan('rss:opml:export', ['file' => $exportPath])
        ->assertSuccessful();

    $content = file_get_contents($exportPath);
    $xml = simplexml_load_string($content);

    $outline = $xml->body->outline[0];
    expect((string) $outline['type'])->toBe('rss');
    expect((string) $outline['text'])->toBe('Test Feed');
    expect((string) $outline['title'])->toBe('Test Feed');
    expect((string) $outline['xmlUrl'])->toBe('https://example.com/feed.xml');
    expect((string) $outline['htmlUrl'])->toBe('https://example.com');

    unlink($exportPath);
});

it('handles special characters in export', function () {
    Feed::factory()->create([
        'title' => 'Tech & Dev ✨',
        'url' => 'https://example.com/feed',
        'folder_id' => null,
    ]);

    $exportPath = tempnam(sys_get_temp_dir(), 'opml_export_').'.opml';

    $this->artisan('rss:opml:export', ['file' => $exportPath])
        ->assertSuccessful();

    $content = file_get_contents($exportPath);

    // Should contain properly escaped entities
    expect($content)->toContain('Tech &amp; Dev ✨');

    // Re-parse to verify round-trip
    $xml = simplexml_load_string($content);
    expect((string) $xml->body->outline[0]['title'])->toBe('Tech & Dev ✨');

    unlink($exportPath);
});
