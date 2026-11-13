<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_form_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 200);
            $table->text('message');
            $table->string('page_url', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_form_messages');
    }
};
