<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('selloship_import_rows', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('import_id');

            $table->unsignedInteger('excel_row')->nullable();

            /*
             * Required calling data
             */
            $table->string('order_id')->nullable();
            $table->string('channel_order_id')->nullable();

            $table->string('customer_mobile')->nullable();
            $table->string('customer_name')->nullable();

            $table->text('product_name')->nullable();

            $table->decimal('amount', 12, 2)->default(0);
            $table->integer('qty')->default(1);

            $table->dateTime('order_date')->nullable();

            $table->text('address')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('pincode')->nullable();

            $table->string('tracking_id')->nullable();
            $table->string('courier_name')->nullable();

            $table->string('order_status')->nullable();
            $table->text('shipment_update')->nullable();
            $table->string('payment_status')->nullable();

            /*
             * Validation
             */
            $table->enum('row_status', [
                'valid',
                'duplicate',
                'failed'
            ])->default('valid');

            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index('import_id');
            $table->index('order_id');
            $table->index('customer_mobile');
            $table->index('row_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('selloship_import_rows');
    }
};
