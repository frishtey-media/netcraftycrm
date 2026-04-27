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
        Schema::create('callingorder', function (Blueprint $table) {
            $table->id();

            // 🔗 Client Reference
            $table->unsignedBigInteger('client_id')->index();

            // 🆔 Order Info
            $table->string('order_id')->index();
            $table->dateTime('order_date')->nullable();

            // 📦 Product Info
            $table->string('product_name')->nullable()->index();
            $table->text('shopify_product_name')->nullable();
            $table->string('weight_per_unit')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('weight', 8, 2)->default(0);
            $table->string('total_weight')->nullable();
            $table->string('barcode')->nullable();

            // 👤 Customer Info
            $table->string('customer_name')->nullable();
            $table->string('father_name')->nullable();
            $table->string('customer_phone')->nullable();

            // 📍 Address
            $table->text('shipping_address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode')->nullable();

            // 💳 Payment
            $table->string('payment_mode')->nullable();
            $table->decimal('amount', 10, 2)->nullable();

            // ⚙️ CRM Fields (IMPORTANT)
            $table->enum('status', ['pending', 'verified', 'not_reachable'])
                ->default('pending')
                ->index();

            $table->unsignedBigInteger('assigned_to')->nullable()->index();

            $table->timestamps();

            // 🔥 Indexes (Performance)
            $table->index(['client_id', 'order_id']);
            $table->index(['assigned_to', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('callingorder');
    }
};
