<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delhivery_imports', function (Blueprint $table) {

            $table->longText('shipping_cost_response')
                ->nullable()
                ->after('shipping_cost');
        });
    }

    public function down(): void
    {
        Schema::table('delhivery_imports', function (Blueprint $table) {

            $table->dropColumn(
                'shipping_cost_response'
            );
        });
    }
};
