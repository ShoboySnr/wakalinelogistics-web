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
        Schema::table('riders', function (Blueprint $table) {
            if (!Schema::hasColumn('riders', 'daily_code')) {
                $table->string('daily_code', 6)->nullable()->after('last_location_update');
            }
            if (!Schema::hasColumn('riders', 'daily_code_date')) {
                $table->date('daily_code_date')->nullable()->after('daily_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('riders', function (Blueprint $table) {
            $table->dropColumn(['daily_code', 'daily_code_date']);
        });
    }
};
