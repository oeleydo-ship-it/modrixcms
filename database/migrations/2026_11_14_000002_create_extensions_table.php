<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extensions', function (Blueprint $table) {
            $table->id();
            $table->string('type', 16);
            $table->string('name', 120);
            $table->string('version', 40);
            $table->boolean('enabled')->default(false);
            $table->timestamp('trusted_at')->nullable();
            $table->foreignId('trusted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('settings')->nullable();
            $table->json('scan')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['type', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extensions');
    }
};
