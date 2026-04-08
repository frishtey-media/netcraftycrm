<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Step 1: Add column first (NO foreign key yet)
        Schema::table('inventory_users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->after('password');
        });

        // Step 2: Assign default role to existing users
        DB::statement('UPDATE inventory_users SET role_id = 1');

        // Step 3: Add foreign key
        Schema::table('inventory_users', function (Blueprint $table) {
            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
