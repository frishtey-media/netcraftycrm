<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {

            if (!Schema::hasColumn('clients', 'token_expires_at')) {
                $table->dateTime('token_expires_at')
                    ->nullable()
                    ->after('shopify_access_token');
            }

            if (!Schema::hasColumn('clients', 'token_updated_at')) {
                $table->dateTime('token_updated_at')
                    ->nullable()
                    ->after('token_expires_at');
            }

            if (!Schema::hasColumn('clients', 'shopify_status')) {
                $table->string('shopify_status', 30)
                    ->default('pending')
                    ->nullable()
                    ->after('token_updated_at');
            }

            if (!Schema::hasColumn('clients', 'shopify_last_error')) {
                $table->text('shopify_last_error')
                    ->nullable()
                    ->after('shopify_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {

            $columns = [
                'token_expires_at',
                'token_updated_at',
                'shopify_status',
                'shopify_last_error',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('clients', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
