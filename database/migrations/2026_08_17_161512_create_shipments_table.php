<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {

            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->string('courier', 50);

            $table->string('awb', 100)
                ->nullable()
                ->index();

            $table->string('status', 50)
                ->default('booked')
                ->index();

            $table->longText('booking_response')->nullable();

            $table->longText('tracking_response')->nullable();

            $table->string('label_path')->nullable();

            $table->string('label_url')->nullable();

            $table->string('pickup_request_id')->nullable();

            $table->timestamp('booked_at')->nullable();

            $table->timestamp('label_generated_at')->nullable();

            $table->timestamp('picked_up_at')->nullable();

            $table->timestamp('delivered_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
