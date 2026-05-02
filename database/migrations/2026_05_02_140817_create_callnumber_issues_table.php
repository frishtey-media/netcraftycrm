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
        Schema::create('callnumber_issues', function (Blueprint $table) {
            $table->id();
            $table->string('callnumber');
            $table->string('staff_name');
            $table->string('client_name');
            $table->dateTime('issued_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('callnumber_issues');
    }
};
