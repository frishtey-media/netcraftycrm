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
        Schema::create('rto_reports', function (Blueprint $table) {
            $table->id();
            $table->string('order_id');
            $table->string('tracking_no');
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('payment_mode')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('product')->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('weight')->nullable();
            $table->date('order_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rto_reports');
    }
};
