<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 40);
            $table->string('label', 80);
            $table->string('handle', 120)->nullable();
            $table->string('url', 500);
            $table->string('followers', 40)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('open_new_tab')->default(true);
            $table->unsignedInteger('clicks_count')->default(0);
            $table->timestamps();
        });

        Schema::create('social_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 20);
            $table->string('platform', 40);
            $table->string('page_url', 500);
            $table->string('page_title', 200)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event_type', 'platform', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_events');
        Schema::dropIfExists('social_profiles');
    }
};
