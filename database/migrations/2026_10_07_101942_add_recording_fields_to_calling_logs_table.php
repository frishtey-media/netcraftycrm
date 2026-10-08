<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calling_logs', function (Blueprint $table) {
            $table->dateTime('call_started_at')
                ->nullable()
                ->after('called_at');

            $table->dateTime('call_ended_at')
                ->nullable()
                ->after('call_started_at');

            $table->unsignedInteger('duration_seconds')
                ->nullable()
                ->after('call_ended_at');

            $table->string('recording_path', 500)
                ->nullable()
                ->after('duration_seconds');

            $table->string('recording_url', 1000)
                ->nullable()
                ->after('recording_path');

            $table->string('recording_status', 30)
                ->nullable()
                ->after('recording_url');
        });
    }

    public function down(): void
    {
        Schema::table('calling_logs', function (Blueprint $table) {
            $table->dropColumn([
                'call_started_at',
                'call_ended_at',
                'duration_seconds',
                'recording_path',
                'recording_url',
                'recording_status',
            ]);
        });
    }
};
