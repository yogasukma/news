<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feed extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'url',
        'site_url',
        'description',
        'favicon_url',
        'folder_id',
        'last_fetched_at',
        'error_count',
        'is_enabled',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'last_fetched_at' => 'datetime',
            'is_enabled' => 'boolean',
        ];
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * Get the favicon URL for this feed.
     * Uses stored favicon_url, or falls back to Google's favicon service.
     */
    public function getFaviconUrlAttribute(): string
    {
        if ($this->attributes['favicon_url'] ?? null) {
            return $this->attributes['favicon_url'];
        }

        $siteUrl = $this->attributes['site_url'] ?? null;

        if ($siteUrl) {
            $domain = parse_url($siteUrl, PHP_URL_HOST);

            if ($domain) {
                return 'https://www.google.com/s2/favicons?domain='.$domain.'&sz=32';
            }
        }

        return '';
    }
}
