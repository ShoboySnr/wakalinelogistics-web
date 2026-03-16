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
            // Guarantor 1
            $table->string('guarantor1_full_name')->nullable()->after('total_deliveries');
            $table->date('guarantor1_dob')->nullable()->after('guarantor1_full_name');
            $table->string('guarantor1_nationality')->default('Nigerian')->after('guarantor1_dob');
            $table->string('guarantor1_nin')->nullable()->after('guarantor1_nationality');
            $table->text('guarantor1_residential_address')->nullable()->after('guarantor1_nin');
            $table->string('guarantor1_phone')->nullable()->after('guarantor1_residential_address');
            $table->string('guarantor1_alt_phone1')->nullable()->after('guarantor1_phone');
            $table->string('guarantor1_alt_phone2')->nullable()->after('guarantor1_alt_phone1');
            $table->text('guarantor1_work_address')->nullable()->after('guarantor1_alt_phone2');
            $table->string('guarantor1_relationship')->nullable()->after('guarantor1_work_address');
            $table->integer('guarantor1_years_known')->nullable()->after('guarantor1_relationship');
            
            // Guarantor 2
            $table->string('guarantor2_full_name')->nullable()->after('guarantor1_years_known');
            $table->date('guarantor2_dob')->nullable()->after('guarantor2_full_name');
            $table->string('guarantor2_nationality')->default('Nigerian')->after('guarantor2_dob');
            $table->string('guarantor2_nin')->nullable()->after('guarantor2_nationality');
            $table->text('guarantor2_residential_address')->nullable()->after('guarantor2_nin');
            $table->string('guarantor2_phone')->nullable()->after('guarantor2_residential_address');
            $table->string('guarantor2_alt_phone1')->nullable()->after('guarantor2_phone');
            $table->string('guarantor2_alt_phone2')->nullable()->after('guarantor2_alt_phone1');
            $table->text('guarantor2_work_address')->nullable()->after('guarantor2_alt_phone2');
            $table->string('guarantor2_relationship')->nullable()->after('guarantor2_work_address');
            $table->integer('guarantor2_years_known')->nullable()->after('guarantor2_relationship');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('riders', function (Blueprint $table) {
            $table->dropColumn([
                'guarantor1_full_name', 'guarantor1_dob', 'guarantor1_nationality', 'guarantor1_nin',
                'guarantor1_residential_address', 'guarantor1_phone', 'guarantor1_alt_phone1', 'guarantor1_alt_phone2',
                'guarantor1_work_address', 'guarantor1_relationship', 'guarantor1_years_known',
                'guarantor2_full_name', 'guarantor2_dob', 'guarantor2_nationality', 'guarantor2_nin',
                'guarantor2_residential_address', 'guarantor2_phone', 'guarantor2_alt_phone1', 'guarantor2_alt_phone2',
                'guarantor2_work_address', 'guarantor2_relationship', 'guarantor2_years_known',
            ]);
        });
    }
};
