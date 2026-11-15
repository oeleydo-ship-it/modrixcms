<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pages can belong to a theme: each theme keeps its own site header and footer
 * (role pages with that theme), and pages imported from a theme's demo remember it.
 * Header and footer pages without a theme are shared by every theme that has none.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropUnique(['role']);
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->string('theme', 120)->nullable()->after('role');
            $table->unique(['role', 'theme']);
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropUnique(['role', 'theme']);
            $table->dropColumn('theme');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->unique('role');
        });
    }
};
