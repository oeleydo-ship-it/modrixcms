<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The site header and footer for visual-builder pages: two special pages (role "header" /
 * "footer") edited in the builder, plus per-page switches to leave them off.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('role', 16)->nullable()->unique()->after('editor');
            $table->boolean('show_site_header')->default(true)->after('role');
            $table->boolean('show_site_footer')->default(true)->after('show_site_header');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropUnique(['role']);
            $table->dropColumn(['role', 'show_site_header', 'show_site_footer']);
        });
    }
};
