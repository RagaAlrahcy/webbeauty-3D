<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_stores', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('type');
            $table->string('platform')->nullable();
            $table->string('url')->nullable();
            $table->string('city', 80)->nullable();
            $table->text('address')->nullable();
            $table->string('maps_url')->nullable();
            $table->string('phone', 30)->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_stores');
    }
};
