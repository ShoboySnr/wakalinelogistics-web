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
            // Add age field for rider (after date_of_birth if it exists, otherwise after phone)
            if (!Schema::hasColumn('riders', 'age')) {
                $table->integer('age')->nullable()->after('phone');
            }
            
            // Add occupation fields for guarantors
            if (!Schema::hasColumn('riders', 'guarantor1_occupation')) {
                $table->string('guarantor1_occupation')->nullable()->after('guarantor1_nationality');
            }
            if (!Schema::hasColumn('riders', 'guarantor2_occupation')) {
                $table->string('guarantor2_occupation')->nullable()->after('guarantor2_nationality');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('riders', function (Blueprint $table) {
            $table->dropColumn(['age', 'guarantor1_occupation', 'guarantor2_occupation']);
        });
    }
};
