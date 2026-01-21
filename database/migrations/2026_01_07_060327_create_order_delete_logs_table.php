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
        Schema::create('order_delete_logs', function (Blueprint $table) {
            $table->id();
            $table->string('barcode')->nullable();
            $table->date('order_date')->nullable();
            $table->timestamp('deleted_at')->useCurrent();
            $table->string('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_delete_logs');
    }
};
