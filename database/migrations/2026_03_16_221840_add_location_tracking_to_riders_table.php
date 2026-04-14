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
            if (!Schema::hasColumn('riders', 'current_latitude')) {
                $table->decimal('current_latitude', 10, 8)->nullable()->after('status');
            }
            if (!Schema::hasColumn('riders', 'current_longitude')) {
                $table->decimal('current_longitude', 11, 8)->nullable()->after('current_latitude');
            }
            if (!Schema::hasColumn('riders', 'last_location_update')) {
                $table->timestamp('last_location_update')->nullable()->after('current_longitude');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('riders', function (Blueprint $table) {
            $table->dropColumn(['current_latitude', 'current_longitude', 'last_location_update']);
        });
    }
};
