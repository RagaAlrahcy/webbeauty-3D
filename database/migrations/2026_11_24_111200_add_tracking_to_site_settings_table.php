<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('ga4_measurement_id', 20)->nullable()->after('address');
            $table->string('meta_pixel_id', 20)->nullable()->after('ga4_measurement_id');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn(['ga4_measurement_id', 'meta_pixel_id']);
        });
    }
};
