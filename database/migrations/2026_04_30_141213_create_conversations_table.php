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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id');
            $table->foreignId('assigned_to')->nullable();

            $table->string('customer_phone');
            $table->text('last_message')->nullable();

            $table->timestamp('last_message_at')->nullable();

            $table->enum('status', ['open', 'closed'])->default('open');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
