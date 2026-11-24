<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();

            // Translatable (id/en) columns are stored as JSON.
            $table->json('name');
            $table->string('slug')->unique();
            $table->json('short_description');
            $table->json('description')->nullable();
            $table->json('ingredients')->nullable();

            $table->unsignedInteger('price')->nullable();

            // 3D design
            $table->string('render_mode')->default('procedural');
            $table->string('container_shape')->nullable();
            $table->string('base_color', 7)->nullable();
            $table->string('material')->nullable();
            $table->string('animation_preset')->default('rotate');
            $table->string('background_theme')->nullable();
            $table->json('camera_position')->nullable();

            $table->boolean('is_featured')->default(false);
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();

            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
