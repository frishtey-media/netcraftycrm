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
        Schema::create('shopify_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('order_id');
            $table->dateTime('order_date')->nullable();
            $table->string('product_name');
            $table->integer('quantity');
            $table->decimal('weight', 8, 2)->default(0);
            $table->string('barcode')->nullable();
            $table->timestamps();

            // 🚫 SAME CLIENT SAME ORDER SAME PRODUCT NOT ALLOWED
            $table->unique(['client_id', 'order_id', 'product_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopify_orders');
    }
};
