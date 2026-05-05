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
        Schema::table('feeds', function (Blueprint $table) {
            $table->unsignedInteger('error_count')->default(0)->after('last_fetched_at');
            $table->boolean('is_enabled')->default(true)->after('error_count');
            $table->string('last_error')->nullable()->after('is_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feeds', function (Blueprint $table) {
            $table->dropColumn(['error_count', 'is_enabled', 'last_error']);
        });
    }
};
