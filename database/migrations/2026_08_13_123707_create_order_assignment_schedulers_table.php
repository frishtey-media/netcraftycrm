<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_assignment_schedulers', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('client_id');

            /*
            |--------------------------------------------------------------------------
            | Multiple order types
            |--------------------------------------------------------------------------
            |
            | Example:
            | ["shopify", "abandoned_checkout", "deliveredreorder"]
            |
            */

            $table->json('order_types');

            /*
            |--------------------------------------------------------------------------
            | Time Window
            |--------------------------------------------------------------------------
            */

            $table->time('start_time')->default('09:00:00');

            $table->time('end_time')->default('17:00:00');

            /*
            |--------------------------------------------------------------------------
            | Days
            |--------------------------------------------------------------------------
            |
            | Example:
            | ["monday","tuesday","wednesday","thursday","friday","saturday"]
            |
            */

            $table->json('days')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Staff Distribution
            |--------------------------------------------------------------------------
            |
            | Example:
            |
            | [
            |   {"staff_id":5,"percentage":40},
            |   {"staff_id":8,"percentage":30},
            |   {"staff_id":9,"percentage":30}
            | ]
            |
            */

            $table->json('staff_assignments');

            /*
            |--------------------------------------------------------------------------
            | Scheduler ON / OFF
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->onDelete('cascade');

            $table->index([
                'client_id',
                'is_active'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_assignment_schedulers');
    }
};
