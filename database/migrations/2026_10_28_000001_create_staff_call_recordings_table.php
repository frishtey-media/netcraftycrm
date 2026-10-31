<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('staff_call_recordings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id')->nullable()->index();
            $table->string('device_phone', 30)->nullable()->index();
            $table->string('customer_phone', 30)->nullable()->index();
            $table->string('call_type', 20)->default('unknown');
            $table->timestamp('call_started_at')->nullable()->index();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('device_record_id', 191)->unique();
            $table->string('recording_path')->nullable();
            $table->string('recording_mime', 100)->nullable();
            $table->unsignedBigInteger('recording_size')->nullable();
            $table->string('upload_status', 30)->default('metadata_only')->index();
            $table->text('upload_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_call_recordings');
    }
};
