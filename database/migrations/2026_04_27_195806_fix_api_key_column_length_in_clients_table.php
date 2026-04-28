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
            $table->dropUnique(['api_key']);
        });
        
        Schema::table('clients', function (Blueprint $table) {
            $table->string('api_key', 100)->nullable()->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique(['api_key']);
        });
        
        Schema::table('clients', function (Blueprint $table) {
            $table->string('api_key', 64)->nullable()->unique()->change();
        });
    }
};
