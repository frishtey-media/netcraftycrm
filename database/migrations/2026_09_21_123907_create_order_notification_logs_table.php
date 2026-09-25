<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_notification_logs', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('order_id');

            $table->unsignedBigInteger('client_id');

            $table->string('status', 50);

            $table->string('provider', 50);

            $table->timestamps();

            $table->unique([
                'order_id',
                'client_id',
                'status'
            ]);

            $table->index('client_id');
            $table->index('provider');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_notification_logs');
    }
};
