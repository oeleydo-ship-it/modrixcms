<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The admin sidebar used to show a placeholder name ("codearena") and badge ("dev") that were
 * saved as settings the first time the appearance was customised. They are now blank by default
 * (the sidebar shows the site name), so saved values that are still the old placeholders are
 * removed. A name or badge someone typed themselves is left alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->where('key', 'admin_store_handle')->where('value', 'codearena')->delete();
        DB::table('settings')->where('key', 'admin_store_badge')->where('value', 'dev')->delete();
    }

    public function down(): void
    {
        // Nothing to restore: the old values were placeholders.
    }
};
