<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path', 300)->unique();
            $table->string('to_url', 500);
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->unsignedInteger('hits')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
        });

        Schema::create('seo_page_views', function (Blueprint $table) {
            $table->id();
            $table->string('path', 300);
            $table->string('page_title', 200)->nullable();
            $table->string('referrer_host', 160)->default('');
            $table->string('device', 20)->default('desktop');
            $table->date('viewed_date');
            $table->unsignedInteger('views')->default(1);
            $table->timestamps();

            $table->unique(['path', 'referrer_host', 'device', 'viewed_date'], 'seo_views_daily_unique');
            $table->index(['viewed_date', 'views']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_page_views');
        Schema::dropIfExists('seo_redirects');
    }
};
