<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Collapse existing duplicate URLs first (keep the oldest row per URL),
        // otherwise the unique index below cannot be created.
        DB::table('articles')
            ->whereNotIn('id', function ($query) {
                $query->selectRaw('MIN(id)')->from('articles')->groupBy('url');
            })
            ->delete();

        Schema::table('articles', function (Blueprint $table) {
            $table->unique('url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropUnique('articles_url_unique');
        });
    }
};
