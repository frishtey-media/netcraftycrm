<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delhivery_imports', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Package / Shipping Configuration
            |--------------------------------------------------------------------------
            */

            if (!Schema::hasColumn(
                'delhivery_imports',
                'package_type'
            )) {
                $table->string('package_type')
                    ->nullable();
            }


            if (!Schema::hasColumn(
                'delhivery_imports',
                'shipping_mode'
            )) {
                $table->string('shipping_mode')
                    ->nullable();
            }


            /*
            |--------------------------------------------------------------------------
            | Serviceability
            |--------------------------------------------------------------------------
            */

            if (!Schema::hasColumn(
                'delhivery_imports',
                'serviceability_status'
            )) {
                $table->string('serviceability_status')
                    ->nullable();
            }


            if (!Schema::hasColumn(
                'delhivery_imports',
                'serviceability_error'
            )) {
                $table->text('serviceability_error')
                    ->nullable();
            }


            /*
            |--------------------------------------------------------------------------
            | Booking
            |--------------------------------------------------------------------------
            */

            if (!Schema::hasColumn(
                'delhivery_imports',
                'booking_error'
            )) {
                $table->text('booking_error')
                    ->nullable();
            }


            /*
            |--------------------------------------------------------------------------
            | Cost
            |--------------------------------------------------------------------------
            */

            if (!Schema::hasColumn(
                'delhivery_imports',
                'shipping_cost'
            )) {
                $table->decimal(
                    'shipping_cost',
                    10,
                    2
                )->nullable();
            }


            /*
            |--------------------------------------------------------------------------
            | Booking timestamps
            |--------------------------------------------------------------------------
            */

            if (!Schema::hasColumn(
                'delhivery_imports',
                'confirmed_at'
            )) {
                $table->timestamp(
                    'confirmed_at'
                )->nullable();
            }


            if (!Schema::hasColumn(
                'delhivery_imports',
                'booking_started_at'
            )) {
                $table->timestamp(
                    'booking_started_at'
                )->nullable();
            }


            if (!Schema::hasColumn(
                'delhivery_imports',
                'booked_at'
            )) {
                $table->timestamp(
                    'booked_at'
                )->nullable();
            }
        });
    }


    public function down(): void
    {
        Schema::table('delhivery_imports', function (Blueprint $table) {

            $columns = [];


            foreach (
                [
                    'package_type',
                    'shipping_mode',
                    'serviceability_status',
                    'serviceability_error',
                    'booking_error',
                    'shipping_cost',
                    'confirmed_at',
                    'booking_started_at',
                    'booked_at',
                ] as $column
            ) {

                if (
                    Schema::hasColumn(
                        'delhivery_imports',
                        $column
                    )
                ) {
                    $columns[] = $column;
                }
            }


            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
