<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delhivery_imports', function (Blueprint $table) {

            $table->id();

            $table->foreignId('client_id')
                ->constrained('clients')
                ->cascadeOnDelete();

            $table->string('order_id', 100)->index();

            $table->dateTime('order_date')->nullable();

            $table->string('shopify_order_id')->nullable();

            $table->string('payment_mode', 30);

            $table->decimal('amount', 12, 2)->default(0);

            $table->string('customer_name');

            $table->string('father_name')->nullable();

            $table->string('customer_phone', 100);

            $table->text('shipping_address');

            $table->string('city')->nullable();

            $table->string('state')->nullable();

            $table->string('pincode', 10);

            $table->text('product')->nullable();

            $table->integer('quantity')->default(1);

            $table->decimal('weight', 10, 2)->nullable();

            $table->integer('age')->nullable();

            $table->string('assigned_staff')->nullable();

            $table->string('status', 50)
                ->default('pending')
                ->index();

            $table->string('awb', 100)
                ->nullable()
                ->index();

            $table->text('error_message')->nullable();

            $table->longText('serviceability_response')->nullable();

            $table->longText('booking_response')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delhivery_imports');
    }
};
