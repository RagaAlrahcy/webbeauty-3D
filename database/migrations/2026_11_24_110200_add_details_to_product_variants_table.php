<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table): void {
            // Optional overrides of the product values (shades and sizes can differ).
            $table->unsignedInteger('price')->nullable()->after('color');
            $table->string('net_content', 30)->nullable()->after('price');
            $table->string('bpom_number', 30)->nullable()->after('net_content');
            $table->string('marketplace_url')->nullable()->after('bpom_number');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->dropColumn(['price', 'net_content', 'bpom_number', 'marketplace_url']);
        });
    }
};
