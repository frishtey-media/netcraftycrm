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
        Schema::table('shipments', function (Blueprint $table) {

            $table->string('pickup_status')
                ->nullable();

            $table->date('pickup_date')
                ->nullable();

            $table->time('pickup_time')
                ->nullable();

            $table->json('pickup_response')
                ->nullable();

            $table->timestamp('pickup_requested_at')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            //
        });
    }
};
