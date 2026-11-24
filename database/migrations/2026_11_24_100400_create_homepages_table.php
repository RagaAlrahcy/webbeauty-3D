<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Single-row table, edited from the ManageHomepage page.
        Schema::create('homepages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hero_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->json('headline')->nullable();
            $table->json('subheadline')->nullable();
            $table->json('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepages');
    }
};
