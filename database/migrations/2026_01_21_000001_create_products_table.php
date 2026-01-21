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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_id', 20)->comment('Product ID from API');
            $table->string('price_group', 10)->comment('Price group');
            $table->string('name', 500)->comment('Product name');
            $table->string('brand', 50)->comment('Brand name (e.g., uniqlo, gu)');
            $table->string('gender', 20)->nullable()->comment('Gender category: WOMEN/MEN/KIDS/BABY/UNISEX');
            $table->string('image_url', 500)->nullable()->comment('Product main image URL');
            $table->unsignedInteger('current_price')->default(0)->comment('Current price in JPY');
            $table->unsignedInteger('lowest_price')->default(0)->comment('Historical lowest price in JPY');
            $table->unsignedInteger('highest_price')->default(0)->comment('Historical highest price in JPY');
            $table->timestamps();

            // Unique constraint: product_id + price_group combination
            $table->unique(['product_id', 'price_group']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
