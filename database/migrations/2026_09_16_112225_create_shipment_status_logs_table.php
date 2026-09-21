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
        Schema::create('shipment_status_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('order_id')->index();

            $table->string('awb', 100)->nullable()->index();

            $table->string('status', 100);

            $table->string('event_id', 200)->unique();

            $table->string('webhook_status', 30)->default('pending');

            $table->unsignedInteger('attempts')->default(0);

            $table->unsignedSmallInteger('response_status')->nullable();

            $table->longText('response_body')->nullable();

            $table->timestamp('sent_at')->nullable();

            $table->timestamps();

            $table->unique([
                'order_id',
                'status'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_status_logs');
    }
};
