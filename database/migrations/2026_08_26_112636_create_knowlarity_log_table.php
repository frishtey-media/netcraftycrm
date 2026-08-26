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
        Schema::create('knowlarity_log', function (Blueprint $table) {
            $table->id();

            $table->date('call_date')->nullable();
            $table->time('call_time')->nullable();

            $table->string('caller_number')->nullable();
            $table->string('call_direction')->nullable();
            $table->string('called_number')->nullable();
            $table->string('call_status')->nullable();
            $table->string('agent_number')->nullable();
            $table->string('call_transfer_status')->nullable();

            $table->integer('caller_duration')->nullable();

            $table->text('recording_url')->nullable();

            $table->string('call_uuid')->nullable()->unique();

            $table->string('hangup_cause')->nullable();
            $table->string('menu_extension')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowlarity_log');
    }
};
