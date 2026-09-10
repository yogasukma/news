<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The legacy UNIQUE(external_id, feed_id) index belongs to the old
     * per-feed dedup strategy. Since Sprint 009, the article URL is the only
     * dedup key — this obsolete index now rejects otherwise-valid inserts
     * (e.g. when a feed changes an article's URL but keeps its guid), so it
     * is removed without replacement.
     */
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropUnique('articles_external_id_feed_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->unique(['external_id', 'feed_id'], 'articles_external_id_feed_id_unique');
        });
    }
};
