<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nl_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email', 200)->unique();
            $table->string('name', 120)->nullable();
            $table->string('status', 20)->default('subscribed');
            $table->string('source', 120)->nullable();
            $table->json('tags')->nullable();
            $table->string('token', 64)->unique();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('nl_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('subject', 200);
            $table->string('preview_text', 300)->nullable();
            $table->text('body_markdown');
            $table->string('target_tag', 80)->nullable();
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nl_campaigns');
        Schema::dropIfExists('nl_subscribers');
    }
};
