<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('records', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique();
            $table->date('date');

            $table->string('barcode')->nullable();
            $table->string('payment_mode');
            $table->decimal('amount', 10, 2);

            $table->string('customer_name');
            $table->string('father_name')->nullable();
            $table->string('customer_phone');

            $table->string('shipping_address_line1');
            $table->string('shipping_address_line2')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('shipping_pincode');

            $table->string('product');
            $table->integer('quantity');
            $table->integer('weight_in_gm');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('records');
    }
};
