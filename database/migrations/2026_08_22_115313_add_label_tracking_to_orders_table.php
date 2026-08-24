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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('label_status')
                ->default('pending')
                ->after('delivery_status');

            $table->timestamp('label_printed_at')
                ->nullable()
                ->after('label_status');

            $table->unsignedInteger('label_print_count')
                ->default(0)
                ->after('label_printed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            //
        });
    }
};
