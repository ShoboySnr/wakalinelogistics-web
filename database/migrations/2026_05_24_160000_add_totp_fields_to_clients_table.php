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
            if (!Schema::hasColumn('clients', 'two_factor_method')) {
                $table->enum('two_factor_method', ['email', 'authenticator'])->default('email')->after('two_factor_enabled');
            }
            if (!Schema::hasColumn('clients', 'two_factor_secret')) {
                $table->string('two_factor_secret')->nullable()->after('two_factor_method');
            }
            if (!Schema::hasColumn('clients', 'two_factor_recovery_codes')) {
                $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $columns = [
                'two_factor_method',
                'two_factor_secret',
                'two_factor_recovery_codes',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('clients', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
