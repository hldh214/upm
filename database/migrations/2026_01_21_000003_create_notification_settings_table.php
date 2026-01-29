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
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('watchlist_id');

            // Price drop notification: notify when price <= target_price, only once
            $table->boolean('price_drop_enabled')->default(false);
            $table->integer('price_drop_target')->nullable(); // Target price in JPY

            // Price change notification: notify when price change >= min_change_amount
            $table->boolean('price_change_enabled')->default(false);
            $table->integer('price_change_min_amount')->nullable(); // Minimum change amount in JPY

            // New product notification settings
            $table->boolean('new_product_enabled')->default(false);

            $table->timestamps();

            $table->unique(['user_id', 'watchlist_id']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
