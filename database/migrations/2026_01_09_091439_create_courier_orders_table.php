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
        Schema::create('courier_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id');
            $table->date('order_date');
            $table->string('barcode');
            $table->string('payment_mode');
            $table->decimal('amount', 10, 2);
            $table->string('customer_name');
            $table->string('customer_father_name');
            $table->string('customer_phone');
            $table->text('shipping_address');
            $table->string('city');
            $table->string('state');
            $table->string('pincode');
            $table->string('product');
            $table->integer('quantity');
            $table->integer('weight');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courier_orders');
    }
};
