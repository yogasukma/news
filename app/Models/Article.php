<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'feed_id',
        'title',
        'url',
        'content',
        'author',
        'published_at',
        'cover_image',
        'external_id',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function feed(): BelongsTo
    {
        return $this->belongsTo(Feed::class);
    }

    /**
     * Normalize an article URL so that permalinks differing only by
     * tracking parameters (or fragment) are treated as the same article.
     */
    public static function normalizeUrl(string $url): string
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return $url;
        }

        $query = [];

        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);

            foreach (array_keys($query) as $key) {
                if (str_starts_with($key, 'utm_') || in_array($key, ['fbclid', 'gclid', 'mc_cid', 'mc_eid', 'ref', 'source'], true)) {
                    unset($query[$key]);
                }
            }
        }

        $parts['query'] = $query === [] ? null : http_build_query($query);

        // Fragments are client-side anchors — never part of a permalink.
        unset($parts['fragment']);

        $normalized = $parts['scheme'].'://'.$parts['host'];

        if (isset($parts['port'])) {
            $normalized .= ':'.$parts['port'];
        }

        if (isset($parts['path'])) {
            $normalized .= $parts['path'];
        }

        if (isset($parts['query'])) {
            $normalized .= '?'.$parts['query'];
        }

        return $normalized;
    }
}
