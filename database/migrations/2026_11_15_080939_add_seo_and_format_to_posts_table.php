<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('content_format', 12)->default('markdown')->after('content');
            $table->string('meta_title', 200)->nullable()->after('meta_description');
            $table->string('focus_keyword', 120)->nullable()->after('meta_title');
            $table->string('canonical_url', 500)->nullable()->after('focus_keyword');
            $table->boolean('noindex')->default(false)->after('canonical_url');
            $table->string('og_title', 200)->nullable()->after('noindex');
            $table->string('og_description', 300)->nullable()->after('og_title');
            $table->string('og_image', 500)->nullable()->after('og_description');
            $table->boolean('ai_generated')->default(false)->after('og_image');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['content_format', 'meta_title', 'focus_keyword', 'canonical_url', 'noindex', 'og_title', 'og_description', 'og_image', 'ai_generated']);
        });
    }
};
