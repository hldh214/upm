<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('new_product_notification_settings')) {
            Schema::create('new_product_notification_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('brand'); // uniqlo, gu
                $table->string('gender'); // men, women, kids, etc.
                $table->boolean('enabled')->default(true);
                $table->timestamps();

                $table->unique(['user_id', 'brand', 'gender']);
                $table->index('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('new_product_notification_settings');
    }
};
