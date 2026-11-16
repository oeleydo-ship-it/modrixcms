<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The media library now holds documents and other files as well as images and videos:
     * `kind` (image, video or file) drives the filter tabs and the pickers, and `uploaded_by`
     * records who added each item.
     */
    public function up(): void
    {
        Schema::table('media_items', function (Blueprint $table) {
            $table->string('kind', 12)->default('image')->after('mime_type');
            $table->foreignId('uploaded_by')->nullable()->after('alt')->constrained('users')->nullOnDelete();
            $table->index(['kind', 'created_at']);
        });

        DB::table('media_items')->where('mime_type', 'like', 'video/%')->update(['kind' => 'video']);
        DB::table('media_items')->where('mime_type', 'not like', 'image/%')->where('mime_type', 'not like', 'video/%')->update(['kind' => 'file']);
    }

    public function down(): void
    {
        Schema::table('media_items', function (Blueprint $table) {
            $table->dropIndex(['kind', 'created_at']);
            $table->dropConstrainedForeignId('uploaded_by');
            $table->dropColumn('kind');
        });
    }
};
