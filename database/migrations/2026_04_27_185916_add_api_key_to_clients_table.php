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
        Schema::table('clients', function (Blueprint $table) {
            $table->string('api_key', 100)->nullable()->unique()->after('email');
            $table->boolean('api_enabled')->default(false)->after('api_key');
            $table->timestamp('api_key_generated_at')->nullable()->after('api_enabled');
            $table->timestamp('api_last_used_at')->nullable()->after('api_key_generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['api_key', 'api_enabled', 'api_key_generated_at', 'api_last_used_at']);
        });
    }
};
