<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            // Regulatory registration numbers (BPOM notification, BPJPH halal certificate).
            $table->string('bpom_number', 30)->nullable()->after('price');
            $table->string('halal_number', 60)->nullable()->after('bpom_number');
            $table->string('net_content', 30)->nullable()->after('halal_number');

            // Lists of enum values (SkinType, SkinConcern, ProductClaim).
            $table->json('skin_types')->nullable()->after('net_content');
            $table->json('skin_concerns')->nullable()->after('skin_types');
            $table->json('claims')->nullable()->after('skin_concerns');

            // [{name, concentration, benefit: {id, en}}]
            $table->json('key_ingredients')->nullable()->after('ingredients');

            // Translatable (id/en).
            $table->json('how_to_use')->nullable()->after('key_ingredients');
            $table->json('warnings')->nullable()->after('how_to_use');

            $table->string('marketplace_url')->nullable()->after('warnings');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn([
                'bpom_number', 'halal_number', 'net_content', 'skin_types', 'skin_concerns', 'claims',
                'key_ingredients', 'how_to_use', 'warnings', 'marketplace_url',
            ]);
        });
    }
};
