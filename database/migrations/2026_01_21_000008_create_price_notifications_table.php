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
        if (! Schema::hasTable('price_notifications')) {
            Schema::create('price_notifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('product_id');
                $table->string('notification_type'); // 'price_drop', 'price_change', 'new_product'
                $table->integer('price_at_notification')->nullable(); // Price at time of notification
                $table->timestamps();

                $table->index(['user_id', 'product_id']);
                $table->index('notification_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_notifications');
    }
};
