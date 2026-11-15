<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 140);
            $table->string('type', 20)->default('bar');
            $table->string('title', 200);
            $table->text('body')->nullable();
            $table->string('badge_text', 60)->nullable();
            $table->string('cta_label', 80)->nullable();
            $table->string('cta_url', 500)->nullable();
            $table->string('coupon_code', 60)->nullable();
            $table->string('bg_color', 20)->default('#111827');
            $table->string('text_color', 20)->default('#ffffff');
            $table->string('accent_color', 20)->default('#f59e0b');
            $table->string('trigger_type', 20)->default('immediate');
            $table->unsignedInteger('trigger_value')->default(0);
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_campaigns');
    }
};
