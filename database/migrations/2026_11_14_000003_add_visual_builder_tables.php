<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The visual page builder: a page's builder document (draft and published), its saved
 * versions, reusable templates (pages, sections, blocks) and the media library.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            // "blocks" = the form-based block editor, "builder" = the visual builder.
            $table->string('editor', 16)->default('blocks')->after('blocks');
            $table->json('document')->nullable()->after('editor');
            $table->json('published_document')->nullable()->after('document');
            $table->unsignedInteger('version')->default(1)->after('published_document');
            $table->unsignedSmallInteger('schema_version')->default(1)->after('version');
            $table->unsignedSmallInteger('saves_since_snapshot')->default(0)->after('schema_version');
            $table->unsignedInteger('published_version')->nullable()->after('saves_since_snapshot');
            $table->timestamp('published_at')->nullable()->after('published_version');
        });

        Schema::create('page_versions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->unsignedSmallInteger('schema_version')->default(1);
            $table->json('document');
            $table->string('label', 120)->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['page_id', 'created_at']);
        });

        Schema::create('builder_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 160);
            $table->string('description', 1000)->nullable();
            $table->string('type', 16); // page | section | block
            $table->string('category', 60)->nullable();
            $table->json('payload');
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'updated_at']);
        });

        Schema::create('media_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 255);
            $table->string('path', 500);
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('alt', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_items');
        Schema::dropIfExists('builder_templates');
        Schema::dropIfExists('page_versions');

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['editor', 'document', 'published_document', 'version', 'schema_version', 'saves_since_snapshot', 'published_version', 'published_at']);
        });
    }
};
