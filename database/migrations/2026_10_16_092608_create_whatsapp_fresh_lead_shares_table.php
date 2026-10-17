
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_fresh_lead_shares', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('callingorder_id');
            $table->unsignedBigInteger('original_staff_id')->nullable();
            $table->unsignedBigInteger('shared_with_staff_id');

            $table->timestamps();

            $table->unique(
                ['callingorder_id', 'shared_with_staff_id'],
                'wa_fresh_lead_share_unique'
            );

            $table->index(['client_id', 'shared_with_staff_id']);
            $table->index('callingorder_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_fresh_lead_shares');
    }
};
