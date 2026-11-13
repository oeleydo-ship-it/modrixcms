<?php

use App\PageBuilder\PageBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pages are now built from blocks. Existing Markdown content becomes a
 * "Page header" + "Text" block, so no page loses its text.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->json('blocks')->nullable()->after('slug');
            $table->boolean('is_home')->default(false)->after('is_published');
        });

        DB::table('pages')->orderBy('id')->each(function ($page) {
            $blocks = [PageBuilder::make('page_header', ['title' => $page->title])];

            if (filled($page->content)) {
                $blocks[] = PageBuilder::make('text', ['content' => $page->content]);
            }

            DB::table('pages')->where('id', $page->id)->update(['blocks' => json_encode($blocks)]);
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('content');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->longText('content')->nullable()->after('slug');
        });

        DB::table('pages')->orderBy('id')->each(function ($page) {
            $text = collect(json_decode($page->blocks ?? '[]', true))
                ->where('type', 'text')
                ->pluck('data.content')
                ->implode("\n\n");

            DB::table('pages')->where('id', $page->id)->update(['content' => $text]);
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['blocks', 'is_home']);
        });
    }
};
