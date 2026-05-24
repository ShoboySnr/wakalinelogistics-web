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
            if (!Schema::hasColumn('clients', 'language')) {
                $table->string('language', 50)->default('English')->after('theme_preference');
            }
            if (!Schema::hasColumn('clients', 'timezone')) {
                $table->string('timezone', 100)->default('UTC+1 (West Africa Time)')->after('language');
            }
            if (!Schema::hasColumn('clients', 'date_format')) {
                $table->string('date_format', 50)->default('DD/MM/YYYY')->after('timezone');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $columns = ['language', 'timezone', 'date_format'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('clients', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
