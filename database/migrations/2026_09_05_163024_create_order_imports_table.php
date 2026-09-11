<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_imports', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('client_id');

            $table->string('file_name');
            $table->string('source')->default('selloship');

            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);

            $table->enum('status', [
                'uploaded',
                'processing',
                'ready',
                'completed',
                'failed'
            ])->default('uploaded');

            $table->unsignedBigInteger('uploaded_by')->nullable();

            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index('client_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_imports');
    }
};
