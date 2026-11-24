<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Single-row table, edited from the ManageSiteSettings page.
        Schema::create('site_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('brand_name');
            $table->json('tagline')->nullable();
            $table->string('primary_color', 7)->default('#b76e79');
            $table->string('accent_color', 7)->default('#f4e1dc');
            $table->string('whatsapp_number')->nullable();
            $table->string('marketplace_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('tiktok_url')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
