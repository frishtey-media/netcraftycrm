<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('callingorder', function (Blueprint $table) {

            $table->string('source_order_id', 100)
                ->nullable()
                ->after('order_id');
        });
    }

    public function down(): void
    {
        Schema::table('callingorder', function (Blueprint $table) {

            $table->dropColumn('source_order_id');
        });
    }
};
