<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('author_name', 120);
            $table->string('author_role', 120)->nullable();
            $table->string('company', 120)->nullable();
            $table->string('author_email', 200)->nullable();
            $table->unsignedTinyInteger('rating')->default(5);
            $table->text('quote');
            $table->string('status', 20)->default('approved');
            $table->boolean('is_featured')->default(false);
            $table->boolean('verified')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['status', 'is_featured', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
