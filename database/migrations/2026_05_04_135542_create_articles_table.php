<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feed_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('url');
            $table->text('content')->nullable();
            $table->string('author')->nullable();
            $table->timestamp('published_at');
            $table->string('cover_image')->nullable();
            $table->string('external_id')->nullable();
            $table->timestamps();

            $table->unique(['external_id', 'feed_id']);
            $table->index('published_at');
            $table->index(['feed_id', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
