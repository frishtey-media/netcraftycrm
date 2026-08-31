<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopify_token_logs', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('client_id');

            $table->string('status', 30);

            $table->text('message')->nullable();

            $table->timestamps();

            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopify_token_logs');
    }
};
