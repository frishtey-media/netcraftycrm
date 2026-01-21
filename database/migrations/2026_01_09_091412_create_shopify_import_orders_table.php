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
        Schema::create('shopify_import_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id');
            $table->date('order_date');
            $table->string('payment_mode');
            $table->decimal('amount', 10, 2);
            $table->string('customer_name');
            $table->string('customer_father_name'); // company
            $table->string('customer_phone');
            $table->text('shipping_address');
            $table->string('city');
            $table->string('state');
            $table->string('pincode');
            $table->string('product');
            $table->integer('quantity');
            $table->string('barcode')->nullable();
            $table->integer('weight')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopify_import_orders');
    }
};
