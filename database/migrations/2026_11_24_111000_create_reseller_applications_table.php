<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reseller_applications', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->string('city', 80);
            $table->string('business_type');
            $table->string('shop_url')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('new');
            $table->text('notes')->nullable();
            $table->string('locale', 5);
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_applications');
    }
};
