<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq_items', function (Blueprint $table) {
            $table->id();
            $table->string('category', 80)->default('General');
            $table->string('question', 300);
            $table->text('answer_markdown');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('helpful_yes')->default(0);
            $table->unsignedInteger('helpful_no')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'category', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faq_items');
    }
};
