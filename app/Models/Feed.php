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
    ];

    protected function casts(): array
    {
        return [
            'last_fetched_at' => 'datetime',
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
}
