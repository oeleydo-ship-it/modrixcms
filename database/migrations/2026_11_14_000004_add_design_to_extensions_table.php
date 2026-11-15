<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Each theme's own design settings (Appearance → Theme settings): colours, typography,
 * buttons, sections and links. Kept apart from the theme's own extra settings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('extensions', function (Blueprint $table) {
            $table->json('design')->nullable()->after('settings');
        });
    }

    public function down(): void
    {
        Schema::table('extensions', function (Blueprint $table) {
            $table->dropColumn('design');
        });
    }
};
