<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_shift_logs', function (Blueprint $table) {

            $table->string('old_order_id')->nullable();
            $table->string('new_order_id')->nullable();

            $table->string('old_order_source')->nullable();
            $table->string('new_order_source')->nullable();

            $table->string('shift_type')->nullable();

            $table->dateTime('old_created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_shift_logs');
    }
};
