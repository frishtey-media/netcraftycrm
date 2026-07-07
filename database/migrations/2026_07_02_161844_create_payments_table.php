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
        Schema::create('payments', function (Blueprint $table) {

            $table->id();

            $table->string('article_number')->unique();

            $table->integer('article_count')->nullable();

            $table->string('cod_invoice_number')->nullable();

            $table->date('delivered_date')->nullable();

            $table->decimal('cod_value', 10, 2)->default(0);

            $table->decimal('cod_commission', 10, 2)->default(0);

            $table->string('office_id')->nullable();

            $table->string('office_name')->nullable();

            $table->string('customer_id')->nullable();

            $table->string('customer_name')->nullable();

            $table->date('bill_date')->nullable();

            $table->string('contract_id')->nullable();

            $table->string('contract_mode')->nullable();

            $table->foreignId('order_id')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
