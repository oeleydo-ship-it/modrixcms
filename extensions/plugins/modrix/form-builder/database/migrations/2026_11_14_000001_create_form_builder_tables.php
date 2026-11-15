<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fb_forms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('slug', 120)->unique();
            $table->text('description')->nullable();
            $table->json('fields');
            $table->json('settings');
            $table->string('status', 20)->default('active');
            $table->unsignedInteger('submissions_count')->default(0);
            $table->timestamps();
        });

        Schema::create('fb_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('fb_forms')->cascadeOnDelete();
            $table->json('data');
            $table->string('status', 20)->default('new');
            $table->text('notes')->nullable();
            $table->string('page_url', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 300)->nullable();
            $table->timestamps();

            $table->index(['form_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fb_submissions');
        Schema::dropIfExists('fb_forms');
    }
};
