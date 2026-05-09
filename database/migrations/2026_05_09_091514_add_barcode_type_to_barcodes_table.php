<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('barcodes', function ($table) {
            $table->enum('barcode_type', ['vpp', 'cod'])
                ->default('vpp')
                ->after('client_id');
        });
    }

    public function down()
    {
        Schema::table('barcodes', function ($table) {
            $table->dropColumn('barcode_type');
        });
    }
};
